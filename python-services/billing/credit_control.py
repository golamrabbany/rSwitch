"""
Credit Control Safety Net — Periodic check for active prepaid calls.

Runs every 30 seconds via Celery Beat. For each active call tracked
in Redis, checks:
1. Client balance — can it cover the next 30 seconds?
2. Reseller balance — can the reseller cover the next 30 seconds?

Disconnects via AMI if either balance is exhausted.

This is a safety net — the primary mechanism is ABSOLUTE_TIMEOUT set by the
outbound handler at call setup time. This task handles edge cases like
concurrent calls draining a shared balance.
"""

import json
import logging
import os
import socket
import time

import redis as redis_lib
from celery import shared_task
from sqlalchemy import text

from shared.config import get_settings
from shared.database import get_session

logger = logging.getLogger(__name__)


@shared_task(name="billing.credit_control.check_balances")
def check_active_call_balances():
    """Safety net: check all active prepaid calls and disconnect if balance exhausted."""
    settings = get_settings()
    r = redis_lib.from_url(settings.redis_url)

    # Find all active call keys (SCAN is non-blocking, unlike KEYS)
    keys = list(r.scan_iter(match="rswitch:active_call:*", count=100))
    if not keys:
        return {"checked": 0, "disconnected": 0, "reseller_disconnected": 0}

    disconnected = 0
    reseller_disconnected = 0
    checked = 0
    channels_to_hangup = []

    with get_session() as session:
        for key in keys:
            data = r.get(key)
            if not data:
                continue

            try:
                call = json.loads(data)
            except (json.JSONDecodeError, TypeError):
                r.delete(key)
                continue

            rate_per_minute = call.get("rate_per_minute", 0)
            if rate_per_minute <= 0:
                continue

            checked += 1
            elapsed = time.time() - call["start_time"]
            cost_30s = (30 / 60) * rate_per_minute
            should_disconnect = False
            disconnect_reason = ""

            # ── Check 1: Client balance (prepaid only) ──
            if call.get("billing_type") == "prepaid":
                client_row = session.execute(
                    text("SELECT balance, credit_limit FROM users WHERE id = :uid"),
                    {"uid": call["user_id"]},
                ).first()

                if client_row:
                    client_available = float(client_row.balance or 0) + float(client_row.credit_limit or 0)
                    cost_so_far = (elapsed / 60) * rate_per_minute
                    remaining = client_available - cost_so_far

                    if remaining < cost_30s:
                        should_disconnect = True
                        disconnect_reason = (
                            f"Client {call['user_id']} balance exhausted: "
                            f"remaining={remaining:.4f}, cost_so_far={cost_so_far:.4f}"
                        )

            # ── Check 2: Reseller balance (if parent is reseller + prepaid) ──
            reseller_id = call.get("reseller_id")
            if not should_disconnect and reseller_id:
                reseller_row = session.execute(
                    text("""
                        SELECT balance, credit_limit, billing_type, role
                        FROM users WHERE id = :rid
                    """),
                    {"rid": reseller_id},
                ).first()

                if reseller_row and reseller_row.role == 'reseller' and reseller_row.billing_type == 'prepaid':
                    reseller_available = float(reseller_row.balance or 0) + float(reseller_row.credit_limit or 0)

                    if reseller_available <= 0:
                        should_disconnect = True
                        reseller_disconnected += 1
                        disconnect_reason = (
                            f"Reseller {reseller_id} balance exhausted: "
                            f"available={reseller_available:.4f}"
                        )

            if should_disconnect:
                channel = call.get("channel")
                if channel:
                    channels_to_hangup.append(channel)
                    logger.warning(
                        f"Credit control: {disconnect_reason}, "
                        f"channel={channel}, elapsed={elapsed:.0f}s — disconnecting"
                    )
                    disconnected += 1
                r.delete(key)

    # Batch hangup: ONE AMI connection for all channels
    if channels_to_hangup:
        _batch_hangup(channels_to_hangup)

    summary = {
        "checked": checked,
        "disconnected": disconnected,
        "reseller_disconnected": reseller_disconnected,
    }
    if disconnected > 0:
        logger.info(f"Credit control: {summary}")
    return summary


def _hangup_channel(channel: str):
    """Send AMI Hangup command to disconnect a channel."""
    host = os.environ.get("AMI_HOST", "127.0.0.1")
    port = int(os.environ.get("AMI_PORT", "5038"))
    user = os.environ.get("AMI_USER", "laravel")
    secret = os.environ.get("AMI_SECRET", "")

    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.settimeout(5)
    try:
        s.connect((host, port))
        s.recv(1024)  # banner

        s.send(f"Action: Login\r\nUsername: {user}\r\nSecret: {secret}\r\n\r\n".encode())
        s.recv(4096)

        s.send(f"Action: Hangup\r\nChannel: {channel}\r\nCause: 0\r\n\r\n".encode())
        s.recv(4096)

        s.send(b"Action: Logoff\r\n\r\n")
    finally:
        s.close()


def _batch_hangup(channels: list[str]):
    """Hangup multiple channels using ONE AMI connection."""
    host = os.environ.get("AMI_HOST", "127.0.0.1")
    port = int(os.environ.get("AMI_PORT", "5038"))
    user = os.environ.get("AMI_USER", "laravel")
    secret = os.environ.get("AMI_SECRET", "")

    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.settimeout(10)
    try:
        s.connect((host, port))
        s.recv(1024)

        s.send(f"Action: Login\r\nUsername: {user}\r\nSecret: {secret}\r\n\r\n".encode())
        s.recv(4096)

        for channel in channels:
            try:
                s.send(f"Action: Hangup\r\nChannel: {channel}\r\nCause: 0\r\n\r\n".encode())
                s.recv(4096)
            except Exception as e:
                logger.error(f"AMI hangup failed for {channel}: {e}")
            time.sleep(0.01)

        s.send(b"Action: Logoff\r\n\r\n")
    except Exception as e:
        logger.error(f"AMI batch hangup failed: {e}")
    finally:
        s.close()

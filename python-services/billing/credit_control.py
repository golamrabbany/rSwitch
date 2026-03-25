"""
Credit Control Safety Net — Periodic check for active prepaid calls.

Runs every 30 seconds via Celery Beat. For each active prepaid call tracked
in Redis, estimates cost so far and disconnects via AMI if the user's balance
cannot cover the next 30 seconds of the call.

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

    # Find all active call keys
    keys = r.keys("rswitch:active_call:*")
    if not keys:
        return {"checked": 0, "disconnected": 0}

    disconnected = 0
    checked = 0

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

            if call.get("billing_type") != "prepaid":
                continue

            rate_per_minute = call.get("rate_per_minute", 0)
            if rate_per_minute <= 0:
                continue

            checked += 1

            # Get current balance
            result = session.execute(
                text("SELECT balance, credit_limit FROM users WHERE id = :uid"),
                {"uid": call["user_id"]},
            ).first()

            if not result:
                continue

            balance = float(result.balance or 0) + float(result.credit_limit or 0)
            elapsed = time.time() - call["start_time"]
            cost_so_far = (elapsed / 60) * rate_per_minute
            remaining = balance - cost_so_far

            # If remaining balance can't cover next 30 seconds
            cost_30s = (30 / 60) * rate_per_minute
            if remaining < cost_30s:
                logger.warning(
                    f"Credit exhausted for user {call['user_id']}, "
                    f"channel {call['channel']}, elapsed={elapsed:.0f}s, "
                    f"cost={cost_so_far:.4f}, remaining={remaining:.4f} — disconnecting"
                )
                try:
                    _hangup_channel(call["channel"])
                    disconnected += 1
                except Exception as e:
                    logger.error(f"Failed to hangup {call['channel']}: {e}")

                r.delete(key)

    summary = {"checked": checked, "disconnected": disconnected}
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

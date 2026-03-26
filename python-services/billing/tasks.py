"""
Celery tasks for billing operations.

Replaces:
- php artisan billing:rate-calls (cron every minute)
  → rate_and_charge (triggered per CDR in real-time)
  → rate_batch (safety net every 2 minutes for missed CDRs)
"""

import json
import logging
import time
from datetime import datetime, timedelta
from decimal import Decimal

import redis as redis_lib
from celery import shared_task
from sqlalchemy import text

from shared.config import get_settings
from shared.database import get_session
from shared.models.call_record import CallRecord
from billing.rating import RatingService
from billing.balance import BalanceService
import os
import socket
from billing.exceptions import RateNotFoundException, InsufficientBalanceException

logger = logging.getLogger(__name__)

# Service instances (initialized once per worker)
_redis_client = None
_rating_service = None
_balance_service = None


def _get_services():
    """Lazy-initialize services (one instance per worker process)."""
    global _redis_client, _rating_service, _balance_service

    if _rating_service is None:
        settings = get_settings()
        _redis_client = redis_lib.from_url(settings.redis_url)
        _rating_service = RatingService(_redis_client)
        _balance_service = BalanceService()

    return _rating_service, _balance_service


@shared_task(
    bind=True,
    max_retries=3,
    default_retry_delay=5,
    name="billing.tasks.rate_and_charge",
)
def rate_and_charge(self, call_record_id: int) -> dict:
    """
    Rate and charge a single CDR. Triggered instantly when a call ends.

    Replaces the batch approach in PHP (billing:rate-calls cron).
    Each CDR is processed within ~100ms of call hangup.
    """
    rating_service, balance_service = _get_services()

    try:
        # Step 1: Rate the call
        result = rating_service.rate_call(call_record_id)

        if result["status"] != "rated":
            return result

        # Step 2: Charge client + reseller in ONE atomic transaction
        try:
            charge_result = balance_service.charge_call(call_record_id)
            result["charged"] = charge_result["client_charged"]
            result["reseller_charged"] = charge_result["reseller_charged"]
        except InsufficientBalanceException as e:
            logger.warning(
                f"rate_and_charge: insufficient balance "
                f"[cdr={call_record_id}, user={e.user_id}, "
                f"amount={e.amount}, available={e.available}]"
            )
            result["charged"] = False
            result["charge_error"] = "insufficient_balance"

        return result

    except Exception as exc:
        logger.error(
            f"rate_and_charge failed [cdr={call_record_id}]: {exc}",
            exc_info=True,
        )
        # Retry with exponential backoff
        raise self.retry(exc=exc, countdown=2 ** self.request.retries)


@shared_task(name="billing.tasks.rate_batch")
def rate_batch() -> dict:
    """
    Safety net: find and rate any CDRs that were missed by the real-time flow.

    Runs every 2 minutes via Celery Beat.
    Equivalent to: php artisan billing:rate-calls
    """
    rating_service, balance_service = _get_services()

    # Look for unrated CDRs from the last 24 hours
    cutoff = datetime.now() - timedelta(hours=24)

    rated = 0
    unbillable = 0
    failed = 0
    charge_failures = 0

    orphaned = 0

    with get_session() as session:
        unrated = (
            session.query(CallRecord.id)
            .filter(
                CallRecord.status == "in_progress",
                CallRecord.disposition == "ANSWERED",
                CallRecord.billsec > 0,
                CallRecord.call_start >= cutoff,
            )
            .order_by(CallRecord.call_start)
            .limit(500)
            .all()
        )

        # Orphan cleanup: CDRs stuck in "in_progress" for > 4 hours
        # with no disposition (call_end_handler crashed before updating).
        # Mark as unbillable so they don't pile up forever.
        stale_cutoff = datetime.now() - timedelta(hours=4)
        stale_cdrs = (
            session.query(CallRecord)
            .filter(
                CallRecord.status == "in_progress",
                CallRecord.disposition.is_(None),
                CallRecord.call_start < stale_cutoff,
                CallRecord.call_start >= cutoff,
            )
            .limit(100)
            .all()
        )
        for cdr in stale_cdrs:
            cdr.status = "unbillable"
            cdr.disposition = "FAILED"
            cdr.hangup_cause = "ORPHANED_CDR"
            cdr.call_end = cdr.call_start  # Mark as zero-duration
            cdr.rated_at = datetime.now()
            orphaned += 1

        if orphaned > 0:
            session.commit()
            logger.warning(f"rate_batch: cleaned up {orphaned} orphaned CDRs")

    if not unrated and orphaned == 0:
        return {"status": "no_unrated", "checked": True}

    logger.info(f"rate_batch: found {len(unrated)} unrated CDRs")

    for (cdr_id,) in unrated:
        try:
            result = rating_service.rate_call(cdr_id)

            if result["status"] == "rated":
                rated += 1
                # Charge client + reseller in one atomic transaction
                try:
                    balance_service.charge_call(cdr_id)
                except InsufficientBalanceException:
                    charge_failures += 1
            else:
                unbillable += 1

        except Exception as e:
            failed += 1
            logger.error(f"rate_batch: failed cdr={cdr_id}: {e}")

            # Mark as failed
            with get_session() as session:
                cdr = session.query(CallRecord).get(cdr_id)
                if cdr:
                    cdr.status = "failed"
                    cdr.rated_at = datetime.now()

    summary = {
        "rated": rated,
        "unbillable": unbillable,
        "failed": failed,
        "charge_failures": charge_failures,
        "orphaned": orphaned,
        "total_processed": rated + unbillable + failed,
    }

    logger.info(f"rate_batch completed: {summary}")
    return summary


@shared_task(name="billing.tasks.hangup_reseller_calls")
def hangup_reseller_calls(reseller_id: int) -> dict:
    """
    Hangup all active calls for a reseller whose balance is exhausted.

    Two-pass approach for reliability:
    1. Redis SCAN — find active_call keys with matching reseller_id (non-blocking)
    2. DB fallback — query call_records for in_progress CDRs (catches any missed)

    Uses ONE AMI connection for all hangups (not one per channel).
    """
    settings = get_settings()
    r = redis_lib.from_url(settings.redis_url)

    channels_to_hangup = []
    redis_keys_to_clean = []

    # ── Pass 1: Redis SCAN (non-blocking, unlike KEYS) ──
    try:
        for key in r.scan_iter(match="rswitch:active_call:*", count=100):
            data = r.get(key)
            if not data:
                continue
            try:
                call = json.loads(data)
            except (json.JSONDecodeError, TypeError):
                continue

            if call.get("reseller_id") == reseller_id:
                channel = call.get("channel")
                if channel:
                    channels_to_hangup.append(channel)
                    redis_keys_to_clean.append(key)
    except Exception as e:
        logger.error(f"hangup_reseller_calls: Redis scan failed: {e}")

    # ── Pass 2: DB fallback for any CDRs not in Redis ──
    try:
        with get_session() as session:
            active_cdrs = session.execute(
                text("""
                    SELECT uuid FROM call_records
                    WHERE reseller_id = :rid AND status = 'in_progress'
                """),
                {"rid": reseller_id},
            ).fetchall()

            found_channels = set(channels_to_hangup)
            for (uuid_val,) in active_cdrs:
                data = r.get(f"rswitch:active_call:{uuid_val}")
                if data:
                    try:
                        call = json.loads(data)
                        channel = call.get("channel")
                        if channel and channel not in found_channels:
                            channels_to_hangup.append(channel)
                            redis_keys_to_clean.append(
                                f"rswitch:active_call:{uuid_val}"
                            )
                    except (json.JSONDecodeError, TypeError):
                        pass
    except Exception as e:
        logger.error(f"hangup_reseller_calls: DB query failed: {e}")

    if not channels_to_hangup:
        return {"reseller_id": reseller_id, "hung_up": 0, "total_found": 0}

    # ── Batch hangup: ONE AMI connection for all channels ──
    hung_up = _batch_hangup_channels(channels_to_hangup)

    # Clean up Redis keys
    for key in redis_keys_to_clean:
        try:
            r.delete(key)
        except Exception:
            pass

    summary = {
        "reseller_id": reseller_id,
        "hung_up": hung_up,
        "total_found": len(channels_to_hangup),
    }

    logger.info(f"hangup_reseller_calls completed: {summary}")
    return summary


def _batch_hangup_channels(channels: list[str]) -> int:
    """
    Hangup multiple channels using ONE AMI connection.
    Opens socket once, authenticates once, sends all hangups, closes once.
    10ms spacing between commands to avoid AMI flood.
    """
    host = os.environ.get("AMI_HOST", "127.0.0.1")
    port = int(os.environ.get("AMI_PORT", "5038"))
    user = os.environ.get("AMI_USER", "laravel")
    secret = os.environ.get("AMI_SECRET", "")

    hung_up = 0
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.settimeout(10)

    try:
        s.connect((host, port))
        s.recv(1024)  # AMI banner

        # Login once
        s.send(f"Action: Login\r\nUsername: {user}\r\nSecret: {secret}\r\n\r\n".encode())
        s.recv(4096)

        # Send all hangups on same connection
        for channel in channels:
            try:
                s.send(f"Action: Hangup\r\nChannel: {channel}\r\nCause: 0\r\n\r\n".encode())
                s.recv(4096)
                hung_up += 1
                logger.info(f"AMI hangup: {channel}")
            except Exception as e:
                logger.error(f"AMI hangup failed for {channel}: {e}")

            time.sleep(0.01)  # 10ms spacing

        # Logoff once
        s.send(b"Action: Logoff\r\n\r\n")
    except Exception as e:
        logger.error(f"AMI batch hangup connection failed: {e}")
    finally:
        s.close()

    return hung_up

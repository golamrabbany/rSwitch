"""
Celery tasks for billing operations.

Replaces:
- php artisan billing:rate-calls (cron every minute)
  → rate_and_charge (triggered per CDR in real-time)
  → rate_batch (safety net every 2 minutes for missed CDRs)
"""

import logging
from datetime import datetime, timedelta
from decimal import Decimal

import redis as redis_lib
from celery import shared_task

from shared.config import get_settings
from shared.database import get_session
from shared.models.call_record import CallRecord
from billing.rating import RatingService
from billing.balance import BalanceService
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

        # Step 2: Charge the user's balance
        total_cost = Decimal(result["total_cost"])
        if total_cost > Decimal("0"):
            try:
                balance_service.charge_call(call_record_id)
                result["charged"] = True
            except InsufficientBalanceException as e:
                logger.warning(
                    f"rate_and_charge: insufficient balance "
                    f"[cdr={call_record_id}, user={e.user_id}, "
                    f"amount={e.amount}, available={e.available}]"
                )
                result["charged"] = False
                result["charge_error"] = "insufficient_balance"
        else:
            result["charged"] = True  # Zero cost, no charge needed

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

    if not unrated:
        return {"status": "no_unrated", "checked": True}

    logger.info(f"rate_batch: found {len(unrated)} unrated CDRs")

    for (cdr_id,) in unrated:
        try:
            result = rating_service.rate_call(cdr_id)

            if result["status"] == "rated":
                rated += 1
                total_cost = Decimal(result["total_cost"])
                if total_cost > Decimal("0"):
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
        "total_processed": rated + unbillable + failed,
    }

    logger.info(f"rate_batch completed: {summary}")
    return summary

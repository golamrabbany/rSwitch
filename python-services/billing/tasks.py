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


@shared_task(name="billing.tasks.daily_call_summary")
def daily_call_summary() -> dict:
    """
    Daily aggregation: create ONE summary transaction per user per day.

    Runs at midnight (00:05). Aggregates all 'charged' CDRs from yesterday
    into a single Transaction per user (client) and per reseller.

    This replaces per-call transaction records — balance is already deducted
    in real-time by charge_call(), this task just creates the audit trail.
    """
    from shared.models.transaction import Transaction
    from shared.models.user import User

    yesterday = (datetime.now() - timedelta(days=1)).date()
    yesterday_start = datetime.combine(yesterday, datetime.min.time())
    yesterday_end = datetime.combine(yesterday, datetime.max.time())

    client_summaries = 0
    reseller_summaries = 0

    with get_session() as session:
        # ── Step 1: Client call charge summaries (skip transit — no user) ──
        client_rows = session.execute(
            text("""
                SELECT user_id,
                       SUM(total_cost) as total_cost,
                       SUM(billable_duration) as total_duration,
                       COUNT(*) as call_count
                FROM call_records
                WHERE status = 'charged'
                AND call_start >= :start AND call_start <= :end
                AND total_cost > 0
                AND call_flow NOT IN ('trunk_to_trunk', 'trunk_to_sip')
                AND user_id > 0
                GROUP BY user_id
            """),
            {"start": yesterday_start, "end": yesterday_end},
        ).fetchall()

        for row in client_rows:
            # Check idempotency: skip if summary already exists for this user+date
            existing = session.execute(
                text("""
                    SELECT id FROM transactions
                    WHERE user_id = :uid AND type = 'daily_call_charge'
                    AND DATE(created_at) = :dt
                    LIMIT 1
                """),
                {"uid": row.user_id, "dt": yesterday},
            ).first()

            if existing:
                continue

            total_cost = Decimal(str(row.total_cost))
            total_min = int(row.total_duration or 0) // 60

            # Get current balance for balance_after
            user = session.query(User).get(row.user_id)
            if not user:
                continue

            txn = Transaction()
            txn.user_id = row.user_id
            txn.type = "daily_call_charge"
            txn.amount = -total_cost
            txn.balance_after = Decimal(str(user.balance))
            txn.reference_type = "daily_summary"
            txn.description = (
                f"Daily calls: {row.call_count} calls, "
                f"{total_min} min — {yesterday.strftime('%b %d, %Y')}"
            )
            txn.created_at = datetime.combine(yesterday, datetime.max.time())
            session.add(txn)
            client_summaries += 1

        # ── Step 2: Reseller call charge summaries ──
        reseller_rows = session.execute(
            text("""
                SELECT reseller_id,
                       SUM(reseller_cost) as total_cost,
                       SUM(billable_duration) as total_duration,
                       COUNT(*) as call_count
                FROM call_records
                WHERE status = 'charged'
                AND call_start >= :start AND call_start <= :end
                AND reseller_cost > 0
                AND reseller_id IS NOT NULL
                AND call_flow NOT IN ('trunk_to_trunk', 'trunk_to_sip')
                GROUP BY reseller_id
            """),
            {"start": yesterday_start, "end": yesterday_end},
        ).fetchall()

        for row in reseller_rows:
            existing = session.execute(
                text("""
                    SELECT id FROM transactions
                    WHERE user_id = :uid AND type = 'daily_reseller_charge'
                    AND DATE(created_at) = :dt
                    LIMIT 1
                """),
                {"uid": row.reseller_id, "dt": yesterday},
            ).first()

            if existing:
                continue

            total_cost = Decimal(str(row.total_cost))
            total_min = int(row.total_duration or 0) // 60

            reseller = session.query(User).get(row.reseller_id)
            if not reseller:
                continue

            txn = Transaction()
            txn.user_id = row.reseller_id
            txn.type = "daily_reseller_charge"
            txn.amount = -total_cost
            txn.balance_after = Decimal(str(reseller.balance))
            txn.reference_type = "daily_summary"
            txn.description = (
                f"Daily reseller costs: {row.call_count} calls, "
                f"{total_min} min — {yesterday.strftime('%b %d, %Y')}"
            )
            txn.created_at = datetime.combine(yesterday, datetime.max.time())
            session.add(txn)
            reseller_summaries += 1

        session.commit()

    summary = {
        "date": str(yesterday),
        "client_summaries": client_summaries,
        "reseller_summaries": reseller_summaries,
    }

    logger.info(f"daily_call_summary completed: {summary}")
    return summary


@shared_task(name="billing.tasks.partition_maintenance")
def partition_maintenance() -> dict:
    """
    Daily partition maintenance for call_records table.

    Runs at 00:10. Three operations:
    1. Create partitions for next 7 days (prevent INSERT failures)
    2. Archive partitions older than 30 days to compressed CSV
    3. Drop archived partitions from MySQL (free disk space)
    """
    import gzip
    import csv
    from pathlib import Path

    archive_dir = Path("/var/backups/rswitch/cdr")
    archive_dir.mkdir(parents=True, exist_ok=True)

    created = 0
    archived = 0
    dropped = 0

    with get_session() as session:
        today = datetime.now().date()

        # ── Step 1: Create partitions for next 7 days ──
        for i in range(1, 8):
            future_date = today + timedelta(days=i)
            partition_name = f"p{future_date.strftime('%Y_%m_%d')}"
            boundary = (future_date + timedelta(days=1)).strftime('%Y-%m-%d')

            # Check if partition already exists
            exists = session.execute(
                text("""
                    SELECT 1 FROM INFORMATION_SCHEMA.PARTITIONS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'call_records'
                    AND PARTITION_NAME = :pname
                """),
                {"pname": partition_name},
            ).first()

            if not exists:
                try:
                    # Reorganize p_future to add new partition before it
                    session.execute(text(f"""
                        ALTER TABLE call_records REORGANIZE PARTITION p_future INTO (
                            PARTITION {partition_name} VALUES LESS THAN (TO_DAYS('{boundary}')),
                            PARTITION p_future VALUES LESS THAN MAXVALUE
                        )
                    """))
                    session.commit()
                    created += 1
                    logger.info(f"partition_maintenance: created {partition_name}")
                except Exception as e:
                    session.rollback()
                    logger.error(f"partition_maintenance: failed to create {partition_name}: {e}")

        # ── Step 2: Find and archive old partitions (> 30 days) ──
        cutoff_date = today - timedelta(days=30)

        partitions = session.execute(
            text("""
                SELECT PARTITION_NAME, TABLE_ROWS
                FROM INFORMATION_SCHEMA.PARTITIONS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'call_records'
                AND PARTITION_NAME != 'p_future'
                AND PARTITION_NAME LIKE 'p2%'
                ORDER BY PARTITION_NAME
            """)
        ).fetchall()

        for row in partitions:
            pname = row.PARTITION_NAME
            # Extract date from partition name: p2026_03_15 → 2026-03-15
            try:
                date_str = pname[1:].replace('_', '-')  # p2026_03_15 → 2026-03-15
                partition_date = datetime.strptime(date_str, '%Y-%m-%d').date()
            except ValueError:
                # Old monthly partition format: p2026_03 → skip (already migrated)
                continue

            if partition_date >= cutoff_date:
                continue  # Not old enough to archive

            # Archive to compressed CSV
            archive_file = archive_dir / f"cdr-{date_str}.csv.gz"
            if not archive_file.exists():
                try:
                    rows = session.execute(
                        text(f"""
                            SELECT * FROM call_records PARTITION ({pname})
                        """)
                    ).fetchall()

                    if rows:
                        columns = rows[0]._fields if hasattr(rows[0], '_fields') else rows[0].keys()
                        with gzip.open(str(archive_file), 'wt', newline='') as f:
                            writer = csv.writer(f)
                            writer.writerow(columns)
                            for r in rows:
                                writer.writerow(r)

                        archived += 1
                        logger.info(
                            f"partition_maintenance: archived {pname} "
                            f"({row.TABLE_ROWS} rows) → {archive_file}"
                        )
                except Exception as e:
                    logger.error(f"partition_maintenance: archive failed for {pname}: {e}")
                    continue

            # ── Step 3: Drop archived partition ──
            if archive_file.exists():
                try:
                    session.execute(text(f"ALTER TABLE call_records DROP PARTITION {pname}"))
                    session.commit()
                    dropped += 1
                    logger.info(f"partition_maintenance: dropped {pname}")
                except Exception as e:
                    session.rollback()
                    logger.error(f"partition_maintenance: drop failed for {pname}: {e}")

    summary = {
        "created": created,
        "archived": archived,
        "dropped": dropped,
    }

    logger.info(f"partition_maintenance completed: {summary}")
    return summary


@shared_task(name="billing.tasks.restore_cdr_archive")
def restore_cdr_archive(year: int, month: int) -> dict:
    """
    Restore archived CDR for a given month back into call_records.

    Creates daily partitions for the month, loads CSV.gz files into them.
    All existing CDR pages (inbound/outbound/search) work automatically.
    Restored partitions auto-drop after 24h via cleanup_restored_partitions.
    """
    import csv
    import gzip
    from pathlib import Path
    from calendar import monthrange

    archive_dir = Path("/var/backups/rswitch/cdr")
    days_in_month = monthrange(year, month)[1]
    restored = 0
    total_rows = 0

    with get_session() as session:
        for day in range(1, days_in_month + 1):
            date_str = f"{year}-{month:02d}-{day:02d}"
            partition_name = f"p{year}_{month:02d}_{day:02d}"
            boundary = (datetime(year, month, day) + timedelta(days=1)).strftime('%Y-%m-%d')
            archive_file = archive_dir / f"cdr-{date_str}.csv.gz"

            if not archive_file.exists():
                continue

            # Check if partition already exists
            exists = session.execute(
                text("""
                    SELECT 1 FROM INFORMATION_SCHEMA.PARTITIONS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'call_records'
                    AND PARTITION_NAME = :pname
                """),
                {"pname": partition_name},
            ).first()

            if exists:
                logger.info(f"restore_cdr_archive: partition {partition_name} already exists, skipping")
                continue

            # Create partition
            try:
                session.execute(text(f"""
                    ALTER TABLE call_records REORGANIZE PARTITION p_future INTO (
                        PARTITION {partition_name} VALUES LESS THAN (TO_DAYS('{boundary}')),
                        PARTITION p_future VALUES LESS THAN MAXVALUE
                    )
                """))
                session.commit()
            except Exception as e:
                session.rollback()
                logger.error(f"restore_cdr_archive: failed to create partition {partition_name}: {e}")
                continue

            # Load data from CSV.gz
            try:
                with gzip.open(str(archive_file), 'rt') as f:
                    reader = csv.reader(f)
                    headers = next(reader)  # Skip header row

                    # Build INSERT statement from headers
                    cols = ', '.join(headers)
                    placeholders = ', '.join([f':{h}' for h in headers])

                    batch = []
                    for row in reader:
                        row_dict = {}
                        for i, h in enumerate(headers):
                            val = row[i] if i < len(row) else None
                            # Convert empty strings to None
                            row_dict[h] = val if val != '' else None
                        batch.append(row_dict)

                        if len(batch) >= 5000:
                            session.execute(
                                text(f"INSERT INTO call_records ({cols}) VALUES ({placeholders})"),
                                batch,
                            )
                            total_rows += len(batch)
                            batch = []

                    if batch:
                        session.execute(
                            text(f"INSERT INTO call_records ({cols}) VALUES ({placeholders})"),
                            batch,
                        )
                        total_rows += len(batch)

                    session.commit()
                    restored += 1
                    logger.info(f"restore_cdr_archive: loaded {archive_file.name}")

            except Exception as e:
                session.rollback()
                logger.error(f"restore_cdr_archive: failed to load {archive_file.name}: {e}")

        # Mark restored month in Redis for auto-cleanup after 24h
        try:
            settings = get_settings()
            r = redis_lib.from_url(settings.redis_url)
            key = f"rswitch:restored_archive:{year}-{month:02d}"
            r.setex(key, 86400, json.dumps({
                "year": year, "month": month,
                "restored_at": datetime.now().isoformat(),
                "partitions_restored": restored,
            }))
        except Exception as e:
            logger.error(f"restore_cdr_archive: failed to set Redis key: {e}")

    summary = {
        "year": year,
        "month": month,
        "partitions_restored": restored,
        "total_rows": total_rows,
    }
    logger.info(f"restore_cdr_archive completed: {summary}")
    return summary


@shared_task(name="billing.tasks.cleanup_restored_partitions")
def cleanup_restored_partitions() -> dict:
    """
    Drop restored archive partitions after 24h.
    Runs hourly via Celery Beat. Checks Redis for expired restore markers.
    """
    settings = get_settings()
    r = redis_lib.from_url(settings.redis_url)
    dropped = 0

    # Scan for any expired restore markers (Redis key already expired = 24h passed)
    # We track active restores and check if they're still in Redis
    # If key is gone (TTL expired), the partitions should be dropped

    # Alternative: scan for restore partitions older than retention period
    with get_session() as session:
        partitions = session.execute(
            text("""
                SELECT PARTITION_NAME
                FROM INFORMATION_SCHEMA.PARTITIONS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'call_records'
                AND PARTITION_NAME != 'p_future'
                AND PARTITION_NAME LIKE 'p2%'
                ORDER BY PARTITION_NAME
            """)
        ).fetchall()

        today = datetime.now().date()
        retention_days = 30

        for (pname,) in partitions:
            try:
                date_str = pname[1:].replace('_', '-')
                partition_date = datetime.strptime(date_str, '%Y-%m-%d').date()
            except ValueError:
                continue

            # Skip partitions within retention window
            if partition_date >= (today - timedelta(days=retention_days)):
                continue

            # This is an old partition — check if it's a restored archive
            month_key = f"rswitch:restored_archive:{partition_date.strftime('%Y-%m')}"
            if r.exists(month_key):
                # Redis key still exists = within 24h window, keep it
                continue

            # Redis key expired OR partition is just old — drop it
            try:
                session.execute(text(f"ALTER TABLE call_records DROP PARTITION {pname}"))
                session.commit()
                dropped += 1
                logger.info(f"cleanup_restored: dropped {pname}")
            except Exception as e:
                session.rollback()
                logger.error(f"cleanup_restored: failed to drop {pname}: {e}")

    return {"dropped": dropped}

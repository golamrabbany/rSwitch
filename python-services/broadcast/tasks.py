"""
Celery tasks for voice broadcast execution.
Uses Asterisk .call files for zero-risk automated dialing.
"""
import json
import os
import time
import logging
import redis
from sqlalchemy import text
from celery_app import celery_app
from database import get_session

logger = logging.getLogger(__name__)

CALL_FILE_DIR = "/var/spool/asterisk/outgoing"
CALL_FILE_TMP = "/tmp"
AGI_HOST = os.environ.get("AGI_HOST", "127.0.0.1")
AGI_PORT = os.environ.get("AGI_PORT", "4573")


def get_redis():
    return redis.Redis(
        host=os.environ.get("REDIS_HOST", "127.0.0.1"),
        port=int(os.environ.get("REDIS_PORT", "6379")),
        db=0,
    )


@celery_app.task(name="broadcast.tasks.process_broadcast")
def process_broadcast(broadcast_id):
    """Main broadcast orchestrator — writes .call files, respects concurrency."""
    session = get_session()
    r = get_redis()

    try:
        broadcast = session.execute(text("""
            SELECT b.*, vf.file_path_asterisk as voice_file_path
            FROM broadcasts b
            JOIN voice_files vf ON b.voice_file_id = vf.id
            WHERE b.id = :id
        """), {"id": broadcast_id}).first()

        if not broadcast:
            logger.error(f"Broadcast {broadcast_id} not found")
            return

        if broadcast.status not in ("queued", "running"):
            logger.info(f"Broadcast {broadcast_id} status is {broadcast.status}, skipping")
            return

        # Set running
        session.execute(text("""
            UPDATE broadcasts SET status = 'running', started_at = IFNULL(started_at, NOW())
            WHERE id = :id
        """), {"id": broadcast_id})
        session.commit()

        logger.info(f"Starting broadcast {broadcast_id}: {broadcast.name}")

        while True:
            # Check pause/cancel signals
            control = r.get(f"rswitch:broadcast:{broadcast_id}:control")
            if control == b"pause":
                logger.info(f"Broadcast {broadcast_id} paused by user")
                return
            if control == b"cancel":
                logger.info(f"Broadcast {broadcast_id} cancelled by user")
                return

            # Check user balance
            user = session.execute(text(
                "SELECT balance, credit_limit FROM users WHERE id = :id"
            ), {"id": broadcast.user_id}).first()

            if user:
                available = float(user.balance) + float(user.credit_limit or 0)
                if available < 0.01:
                    logger.warning(f"Broadcast {broadcast_id}: insufficient balance, pausing")
                    session.execute(text(
                        "UPDATE broadcasts SET status = 'paused' WHERE id = :id"
                    ), {"id": broadcast_id})
                    session.commit()
                    return

            # Count active (queued + dialing)
            active = session.execute(text("""
                SELECT COUNT(*) as cnt FROM broadcast_numbers
                WHERE broadcast_id = :id AND status IN ('queued', 'dialing')
            """), {"id": broadcast_id}).first()

            active_count = active.cnt if active else 0

            if active_count >= broadcast.max_concurrent:
                time.sleep(2)
                session.expire_all()  # refresh session cache
                continue

            # Get next pending number
            number = session.execute(text("""
                SELECT * FROM broadcast_numbers
                WHERE broadcast_id = :id AND status = 'pending'
                ORDER BY id ASC LIMIT 1
            """), {"id": broadcast_id}).first()

            if not number:
                # No more pending — check if all done
                still_active = session.execute(text("""
                    SELECT COUNT(*) as cnt FROM broadcast_numbers
                    WHERE broadcast_id = :id AND status IN ('queued', 'dialing')
                """), {"id": broadcast_id}).first()

                if still_active and still_active.cnt > 0:
                    time.sleep(2)
                    session.expire_all()
                    continue
                else:
                    break  # All done

            # Select trunk for this destination
            trunk = _select_trunk(session, number.phone_number)
            if not trunk:
                session.execute(text("""
                    UPDATE broadcast_numbers SET status = 'failed',
                    error_reason = 'No route found', updated_at = NOW()
                    WHERE id = :id
                """), {"id": number.id})
                session.execute(text("""
                    UPDATE broadcasts SET failed_count = failed_count + 1,
                    dialed_count = dialed_count + 1 WHERE id = :bid
                """), {"bid": broadcast_id})
                session.commit()
                continue

            # Apply dial manipulation
            dial_number = _apply_manipulation(number.phone_number, trunk)
            dial_string = f"PJSIP/{dial_number}@trunk-{trunk.direction}-{trunk.id}"

            # Write .call file
            _write_call_file(number, broadcast, dial_string)

            # Mark as queued
            session.execute(text("""
                UPDATE broadcast_numbers SET status = 'queued',
                last_attempt_at = NOW(), updated_at = NOW()
                WHERE id = :id
            """), {"id": number.id})
            session.commit()

            time.sleep(0.3)  # don't flood Asterisk

        # Wait for remaining active numbers (max 10 minutes)
        _wait_for_completion(session, broadcast_id, timeout=600)

        # Mark completed
        session.execute(text("""
            UPDATE broadcasts SET status = 'completed', completed_at = NOW()
            WHERE id = :id AND status = 'running'
        """), {"id": broadcast_id})
        session.commit()

        logger.info(f"Broadcast {broadcast_id} completed")

    except Exception as e:
        logger.error(f"Broadcast {broadcast_id} error: {e}", exc_info=True)
        try:
            session.execute(text(
                "UPDATE broadcasts SET status = 'failed' WHERE id = :id"
            ), {"id": broadcast_id})
            session.commit()
        except Exception:
            pass
    finally:
        session.close()


def _write_call_file(number, broadcast, dial_string):
    """Write .call file — atomic (write to /tmp, move to spool)."""
    survey_config = json.dumps(broadcast.survey_config) if broadcast.survey_config else "{}"

    content = f"""Channel: {dial_string}
CallerID: "{broadcast.caller_id_name or 'Broadcast'}" <{broadcast.caller_id_number or '0000'}>
MaxRetries: {broadcast.retry_attempts or 0}
RetryTime: {broadcast.retry_delay or 300}
WaitTime: {broadcast.ring_timeout or 30}
Context: from-broadcast
Extension: s
Priority: 1
Set: BROADCAST_ID={broadcast.id}
Set: BROADCAST_NUMBER_ID={number.id}
Set: VOICE_FILE={broadcast.voice_file_path}
Set: BROADCAST_TYPE={broadcast.type}
Set: SURVEY_CONFIG={survey_config}
Set: RSWITCH_USER_ID={broadcast.user_id}
Set: RSWITCH_SIP_ACCOUNT_ID={broadcast.sip_account_id}
Set: BROADCAST_CALLEE={number.phone_number}
"""

    temp_path = f"{CALL_FILE_TMP}/bc_{broadcast.id}_{number.id}.call"
    final_path = f"{CALL_FILE_DIR}/bc_{broadcast.id}_{number.id}.call"

    with open(temp_path, "w") as f:
        f.write(content)
    os.chmod(temp_path, 0o666)
    os.rename(temp_path, final_path)


def _select_trunk(session, destination):
    """Select best trunk for destination using longest prefix match."""
    return session.execute(text("""
        SELECT tr.*, t.name as trunk_name, t.host, t.direction,
               tr.remove_prefix, tr.add_prefix
        FROM trunk_routes tr
        JOIN trunks t ON tr.trunk_id = t.id
        WHERE tr.status = 'active' AND t.status = 'active'
        AND t.direction IN ('outgoing', 'both')
        AND :dest LIKE CONCAT(tr.prefix, '%')
        ORDER BY LENGTH(tr.prefix) DESC, tr.priority ASC, tr.weight DESC
        LIMIT 1
    """), {"dest": destination}).first()


def _apply_manipulation(number, trunk):
    """Apply remove_prefix and add_prefix from trunk route."""
    result = number
    if trunk.remove_prefix and result.startswith(trunk.remove_prefix):
        result = result[len(trunk.remove_prefix):]
    if trunk.add_prefix:
        result = trunk.add_prefix + result
    return result


def _wait_for_completion(session, broadcast_id, timeout=600):
    """Wait for all queued/dialing numbers to finish."""
    start = time.time()
    while time.time() - start < timeout:
        active = session.execute(text("""
            SELECT COUNT(*) as cnt FROM broadcast_numbers
            WHERE broadcast_id = :id AND status IN ('queued', 'dialing')
        """), {"id": broadcast_id}).first()

        if not active or active.cnt == 0:
            return
        time.sleep(3)
        session.expire_all()


@celery_app.task(name="broadcast.tasks.check_scheduled_broadcasts")
def check_scheduled_broadcasts():
    """Beat task: every 60s, start broadcasts where scheduled_at <= now."""
    session = get_session()
    try:
        due = session.execute(text("""
            SELECT id FROM broadcasts
            WHERE status = 'scheduled' AND scheduled_at <= NOW()
        """)).fetchall()

        for row in due:
            session.execute(text(
                "UPDATE broadcasts SET status = 'queued' WHERE id = :id"
            ), {"id": row.id})
            session.commit()
            process_broadcast.delay(row.id)
            logger.info(f"Scheduled broadcast {row.id} queued for processing")
    finally:
        session.close()


@celery_app.task(name="broadcast.tasks.cleanup_stuck_broadcast_numbers")
def cleanup_stuck_broadcast_numbers():
    """Beat task: every 2 min, mark old queued/dialing numbers as failed."""
    session = get_session()
    try:
        result = session.execute(text("""
            UPDATE broadcast_numbers
            SET status = 'failed', error_reason = 'No answer after retries',
                updated_at = NOW()
            WHERE status IN ('queued', 'dialing')
            AND updated_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        """))

        if result.rowcount > 0:
            logger.info(f"Cleaned up {result.rowcount} stuck broadcast numbers")

            # Update broadcast counters
            session.execute(text("""
                UPDATE broadcasts b SET
                    failed_count = (SELECT COUNT(*) FROM broadcast_numbers
                        WHERE broadcast_id = b.id AND status = 'failed'),
                    dialed_count = (SELECT COUNT(*) FROM broadcast_numbers
                        WHERE broadcast_id = b.id AND status != 'pending')
                WHERE b.status = 'running'
            """))

        session.commit()
    finally:
        session.close()

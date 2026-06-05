"""Write the call.listen.stop audit row directly from the engine.

Mirrors Laravel's AuditService::log() column shape for `audit_logs`.
"""
import json
import logging

logger = logging.getLogger(__name__)


def build_listen_stop_params(uid: int, linked_id: str, seconds: int) -> dict:
    return {
        "user_id": uid,
        "action": "call.listen.stop",
        "auditable_type": "system",
        "auditable_id": 0,
        "new_values": json.dumps({"linked_id": linked_id, "duration_seconds": seconds}),
        "ip_address": "0.0.0.0",
        "user_agent": "rswitch-engine",
    }


def write_listen_stop(uid: int, linked_id: str, seconds: int):
    """Insert a call.listen.stop audit row (best-effort)."""
    params = build_listen_stop_params(uid, linked_id, seconds)
    try:
        from sqlalchemy import text
        from shared.database import get_sync_engine

        engine = get_sync_engine()
        with engine.connect() as conn:
            conn.execute(
                text("""
                    INSERT INTO audit_logs
                    (user_id, action, auditable_type, auditable_id,
                     old_values, new_values, ip_address, user_agent, created_at)
                    VALUES
                    (:user_id, :action, :auditable_type, :auditable_id,
                     NULL, :new_values, :ip_address, :user_agent, NOW())
                """),
                params,
            )
            conn.commit()
    except Exception as e:
        logger.warning(f"write_listen_stop failed: {e}")

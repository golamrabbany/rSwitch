"""
AGI handler for voice broadcast calls.
Runs when callee answers a broadcast call placed via .call file.
Plays voice file, collects DTMF for survey, creates CDR, bills call.
"""
import json
import uuid
import logging
from datetime import datetime
from decimal import Decimal
from sqlalchemy import text

logger = logging.getLogger(__name__)


class BroadcastCallHandler:
    """Handles AGI for broadcast calls — runs when callee answers."""

    async def handle(self, agi, session):
        try:
            # Read variables set by .call file
            broadcast_id = await agi.get_variable("BROADCAST_ID")
            number_id = await agi.get_variable("BROADCAST_NUMBER_ID")
            voice_file = await agi.get_variable("VOICE_FILE")
            broadcast_type = await agi.get_variable("BROADCAST_TYPE") or "simple"
            callee = await agi.get_variable("BROADCAST_CALLEE") or ""
            user_id = await agi.get_variable("RSWITCH_USER_ID")
            sip_account_id = await agi.get_variable("RSWITCH_SIP_ACCOUNT_ID")
            caller_id = await agi.get_variable("CALLERID(num)") or ""

            if not broadcast_id or not number_id:
                logger.error("Missing broadcast variables")
                return

            broadcast_id = int(broadcast_id)
            number_id = int(number_id)
            user_id = int(user_id) if user_id else 0
            sip_account_id = int(sip_account_id) if sip_account_id else None

            call_start = datetime.utcnow()
            survey_response = None

            # --- Play voice file ---
            try:
                if broadcast_type == "simple":
                    await agi.stream_file(voice_file)

                elif broadcast_type == "survey":
                    raw_config = await agi.get_variable("SURVEY_CONFIG") or "{}"
                    survey_config = json.loads(raw_config)
                    max_digits = survey_config.get("max_digits", 1)
                    timeout_ms = survey_config.get("timeout", 5) * 1000
                    max_retries = survey_config.get("max_retries", 2)
                    options = survey_config.get("options", {})

                    for attempt in range(max_retries + 1):
                        result = await agi.get_data(voice_file, timeout_ms, max_digits)
                        if result and str(result) in options:
                            survey_response = str(result)
                            break
            except Exception as e:
                logger.debug(f"Broadcast playback interrupted (callee hangup): {e}")

            call_end = datetime.utcnow()
            duration = max(int((call_end - call_start).total_seconds()), 1)

            # --- Rate lookup: broadcast → fallback regular ---
            rate = self._lookup_rate(session, user_id, callee, "broadcast")
            if not rate:
                rate = self._lookup_rate(session, user_id, callee, "regular")

            rate_per_minute = float(rate.rate_per_minute) if rate else 0
            connection_fee = float(rate.connection_fee) if rate else 0
            cost = round((duration / 60) * rate_per_minute + connection_fee, 4)

            # --- Deduct balance ---
            if cost > 0:
                session.execute(text(
                    "UPDATE users SET balance = balance - :cost WHERE id = :uid"
                ), {"cost": cost, "uid": user_id})

            # --- Create CDR ---
            cdr_uuid = str(uuid.uuid4())
            session.execute(text("""
                INSERT INTO call_records (
                    uuid, user_id, sip_account_id, call_type, broadcast_id,
                    call_flow, caller, callee, caller_id,
                    call_start, call_end, duration, billsec, billable_duration,
                    rate_per_minute, connection_fee, total_cost,
                    disposition, status, destination, matched_prefix,
                    rate_group_id, created_at
                ) VALUES (
                    :uuid, :uid, :sid, 'broadcast', :bid,
                    'sip_to_trunk', :caller, :callee, :caller_id,
                    :start, :end, :dur, :dur, :dur,
                    :rpm, :cfee, :cost,
                    'ANSWERED', 'completed', :dest, :prefix,
                    :rgid, NOW()
                )
            """), {
                "uuid": cdr_uuid, "uid": user_id, "sid": sip_account_id,
                "bid": broadcast_id, "caller": caller_id, "callee": callee,
                "caller_id": caller_id, "start": call_start, "end": call_end,
                "dur": duration, "rpm": rate_per_minute, "cfee": connection_fee,
                "cost": cost,
                "dest": rate.destination if rate else "",
                "prefix": rate.prefix if rate else "",
                "rgid": int(rate.rate_group_id) if rate else None,
            })

            cdr_row = session.execute(text("SELECT LAST_INSERT_ID() as id")).first()
            cdr_id = cdr_row.id if cdr_row else None

            # --- Update broadcast_numbers ---
            session.execute(text("""
                UPDATE broadcast_numbers SET
                    status = 'completed', duration = :dur, cost = :cost,
                    survey_response = :survey, call_record_id = :cdr,
                    answered_at = :answered, attempt_count = attempt_count + 1,
                    last_attempt_at = NOW(), updated_at = NOW()
                WHERE id = :id
            """), {
                "dur": duration, "cost": cost, "survey": survey_response,
                "cdr": cdr_id, "answered": call_start, "id": number_id,
            })

            # --- Update broadcast counters ---
            session.execute(text("""
                UPDATE broadcasts SET
                    dialed_count = dialed_count + 1,
                    answered_count = answered_count + 1,
                    total_cost = total_cost + :cost
                WHERE id = :bid
            """), {"cost": cost, "bid": broadcast_id})

            session.commit()

            logger.info(f"Broadcast call completed: broadcast={broadcast_id} "
                       f"number={number_id} duration={duration}s cost={cost} "
                       f"survey={survey_response}")

        except Exception as e:
            logger.error(f"Broadcast handler error: {e}", exc_info=True)
            try:
                session.rollback()
            except Exception:
                pass

    def _lookup_rate(self, session, user_id, destination, rate_type):
        """Longest prefix match with rate_type."""
        user = session.execute(text(
            "SELECT rate_group_id FROM users WHERE id = :id"
        ), {"id": user_id}).first()

        if not user or not user.rate_group_id:
            return None

        return session.execute(text("""
            SELECT * FROM rates
            WHERE rate_group_id = :rg AND :dest LIKE CONCAT(prefix, '%')
            AND status = 'active' AND rate_type = :rt
            ORDER BY LENGTH(prefix) DESC LIMIT 1
        """), {
            "rg": user.rate_group_id,
            "dest": destination,
            "rt": rate_type,
        }).first()

"""
AGI handler for voice broadcast calls.
Runs when callee answers a broadcast call placed via .call file.
Plays voice file, collects DTMF for survey, creates CDR, bills call.
"""
import json
import uuid
import logging
from datetime import datetime
from sqlalchemy import text

from broadcast.formatting import build_broadcast_cdr_params

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
            outgoing_trunk_id = await agi.get_variable("RSWITCH_OUTGOING_TRUNK_ID")
            reseller_id = await agi.get_variable("RSWITCH_RESELLER_ID")
            caller_id = await agi.get_variable("CALLERID(num)") or ""

            if not broadcast_id or not number_id:
                logger.error("Missing broadcast variables")
                return

            broadcast_id = int(broadcast_id)
            number_id = int(number_id)
            user_id = int(user_id) if user_id else 0
            sip_account_id = int(sip_account_id) if sip_account_id else None
            outgoing_trunk_id = int(outgoing_trunk_id) if outgoing_trunk_id else None
            reseller_id = int(reseller_id) if reseller_id else None

            # Engine stores LOCAL time (GMT+6); the rest of the system uses
            # NOW(). Never datetime.utcnow() here or broadcast CDRs land 6h off.
            call_start = datetime.now()
            survey_response = None

            # --- Play voice file ---
            try:
                if broadcast_type == "simple":
                    await agi.stream_file(voice_file)

                elif broadcast_type == "survey":
                    raw_config = await agi.get_variable("SURVEY_CONFIG") or "{}"
                    survey_config = json.loads(raw_config)

                    if survey_config.get("version") == 2:
                        # Multi-question flow
                        questions = survey_config.get("questions", [])
                        responses = {}

                        for q in questions:
                            q_type = q.get("type", "question")
                            voice_path = q.get("voice_file_path", voice_file)
                            # Remove .wav extension if present (Asterisk adds it)
                            if voice_path and voice_path.endswith(".wav"):
                                voice_path = voice_path[:-4]

                            if q_type == "intro":
                                # Play intro without DTMF collection
                                await agi.stream_file(voice_path)
                                continue

                            # type == "question"
                            key = q.get("key", "q1")
                            max_digits = q.get("max_digits", 1)
                            timeout_ms = q.get("timeout", 10) * 1000
                            max_retries = q.get("max_retries", 2)
                            options = q.get("options", {})

                            for attempt in range(max_retries + 1):
                                result = await agi.get_data(voice_path, timeout_ms, max_digits)
                                if result and str(result) in options:
                                    responses[key] = str(result)
                                    break

                            # If no valid response, record null
                            if key not in responses:
                                responses[key] = None

                        survey_response = json.dumps(responses) if responses else None

                    else:
                        # Legacy single-question (backward compat)
                        max_digits = survey_config.get("max_digits", 1)
                        timeout_ms = survey_config.get("timeout", 5) * 1000
                        max_retries = survey_config.get("max_retries", 2)
                        options = survey_config.get("options", {})

                        survey_response = None
                        for attempt in range(max_retries + 1):
                            result = await agi.get_data(voice_file, timeout_ms, max_digits)
                            if result and str(result) in options:
                                survey_response = json.dumps({"q1": str(result)})
                                break
            except Exception as e:
                logger.debug(f"Broadcast playback interrupted (callee hangup): {e}")

            call_end = datetime.now()
            duration = max(int((call_end - call_start).total_seconds()), 1)

            # --- Create the CDR as a billable, UNRATED trunk call ---
            # No inline cost: the shared rater (rate_call) fills total_cost /
            # reseller_cost / trunk_cost / matched_prefix, applying BD-MSISDN
            # normalize, the increment policy, dual client+reseller billing,
            # and trunk cost — so broadcast billing can never drift from
            # regular outbound billing.
            cdr_uuid = str(uuid.uuid4())
            params = build_broadcast_cdr_params(
                uuid=cdr_uuid, user_id=user_id, sip_account_id=sip_account_id,
                reseller_id=reseller_id, outgoing_trunk_id=outgoing_trunk_id,
                broadcast_id=broadcast_id, caller=caller_id, callee=callee,
                caller_id=caller_id, call_start=call_start, call_end=call_end,
                duration=duration,
            )
            session.execute(text("""
                INSERT INTO call_records (
                    uuid, user_id, sip_account_id, reseller_id, outgoing_trunk_id,
                    call_type, broadcast_id, call_flow,
                    caller, callee, caller_id,
                    call_start, call_end, duration, billsec, billable_duration,
                    rate_per_minute, connection_fee, total_cost,
                    disposition, status, created_at
                ) VALUES (
                    :uuid, :user_id, :sip_account_id, :reseller_id, :outgoing_trunk_id,
                    :call_type, :broadcast_id, :call_flow,
                    :caller, :callee, :caller_id,
                    :call_start, :call_end, :duration, :billsec, :billsec,
                    0, 0, 0,
                    :disposition, :status, NOW()
                )
            """), params)

            cdr_row = session.execute(text("SELECT LAST_INSERT_ID() as id")).first()
            cdr_id = cdr_row.id if cdr_row else None
            session.commit()

            # --- Rate + charge through the SAME pipeline as regular calls ---
            cost = self._rate_and_charge(cdr_id)

            # --- Update broadcast_numbers with the rated cost ---
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

    def _rate_and_charge(self, cdr_id):
        """Rate + charge the broadcast CDR via the shared billing pipeline.

        Same path as a regular outbound call: rate_call (BD-MSISDN normalize,
        increment policy, dual client/reseller + trunk cost) then charge_call
        (atomic dual debit, prepaid check, reseller auto-block, idempotent).
        Returns the rated client cost for the broadcast counters.

        On any failure the CDR is left status='in_progress', so the billing
        safety net (billing.tasks.rate_batch, every 2 min) still bills it; we
        return 0 for the live counter in that case.
        """
        if not cdr_id:
            return 0
        try:
            import redis as redis_lib
            from billing.rating import RatingService
            from billing.balance import BalanceService
            from shared.config import get_settings
            from shared.database import get_session

            r = redis_lib.from_url(get_settings().redis_url)
            result = RatingService(r).rate_call(cdr_id)
            if result.get("status") == "rated":
                BalanceService().charge_call(cdr_id)

            with get_session() as s:
                row = s.execute(text(
                    "SELECT total_cost FROM call_records WHERE id = :id"
                ), {"id": cdr_id}).first()
            return float(row.total_cost) if row and row.total_cost is not None else 0
        except Exception as e:
            logger.error(
                f"Broadcast rate/charge failed [cdr={cdr_id}]: {e} "
                f"— left for rate_batch safety net", exc_info=True
            )
            return 0

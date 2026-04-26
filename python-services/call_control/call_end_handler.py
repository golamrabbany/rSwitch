"""
Call End Handler — Port of app/Services/Agi/CallEndHandler.php

Handles call completion and CDR finalization.
Called from extensions.conf [hangup-handler] context via FastAGI.

Flow:
1. Read CDR_UUID from channel
2. Read call result variables (DIALSTATUS, duration, billsec)
3. Map DIALSTATUS to disposition
4. Update call_records with final data
5. Trigger billing for answered trunk calls
"""

import logging
import os
from datetime import datetime

import redis as redis_lib
from sqlalchemy import text
from sqlalchemy.orm import Session

from call_control.agi_protocol import AgiConnection

logger = logging.getLogger(__name__)

# Map Asterisk DIALSTATUS to CDR disposition
DIALSTATUS_MAP = {
    "ANSWER": "ANSWERED",
    "BUSY": "BUSY",
    "NOANSWER": "NO ANSWER",
    "CANCEL": "NO ANSWER",
    "CONGESTION": "FAILED",
    "CHANUNAVAIL": "FAILED",
    "DONTCALL": "FAILED",
    "TORTURE": "FAILED",
    "INVALIDARGS": "FAILED",
}


class CallEndHandler:
    """Handles call completion and CDR update via AGI."""

    async def handle(self, agi: AgiConnection, session: Session) -> None:
        try:
            await self._process(agi, session)
        except Exception as e:
            logger.error(f"CallEnd handler error: {e}", exc_info=True)

    async def _process(self, agi: AgiConnection, session: Session) -> None:
        # 1. Get CDR UUID
        cdr_uuid = await agi.get_variable("CDR_UUID")
        if not cdr_uuid:
            await agi.verbose("rSwitch: No CDR_UUID — skipping")
            return

        # 2. Read call result variables.
        # ${CDR(billsec)} is 0 in the hangup-handler context (CDR not yet
        # finalized). Use ANSWEREDTIME / DIALEDTIME — both set when Dial()
        # returns and inherited into hangup handlers.
        dial_status = await agi.get_variable("DIALSTATUS") or "CANCEL"
        answered_str = await agi.get_variable("ANSWEREDTIME") or ""
        dialed_str = await agi.get_variable("DIALEDTIME") or ""
        # Legacy fallbacks (older calls / different dialplan paths).
        duration_str = answered_str or await agi.get_variable("CALL_DURATION") or "0"
        billsec_str = answered_str or await agi.get_variable("CALL_BILLSEC") or "0"
        hangup_cause = await agi.get_variable("HANGUPCAUSE") or ""

        billsec = int(answered_str) if answered_str.isdigit() else 0
        ring_time = int(dialed_str) if dialed_str.isdigit() else 0
        duration = billsec + ring_time
        if duration == 0 and duration_str.isdigit():
            duration = int(duration_str)

        # 3. Map to disposition
        disposition = DIALSTATUS_MAP.get(dial_status.upper(), "FAILED")

        # 4. Determine status
        # Check call flow to decide billing status
        cdr = session.execute(
            text("SELECT id, call_flow, status, disposition FROM call_records WHERE uuid = :uuid LIMIT 1"),
            {"uuid": cdr_uuid},
        ).first()

        if not cdr:
            await agi.verbose(f"rSwitch: CDR {cdr_uuid} not found")
            return

        # Skip if CDR is already finalized (e.g., FAILED for unregistered callee)
        if cdr.status in ("unbillable", "completed", "rated", "failed") and cdr.disposition == "FAILED":
            await agi.verbose(f"rSwitch: CDR {cdr_uuid} already finalized ({cdr.disposition})")
            return

        # Determine final status
        if disposition != "ANSWERED" or billsec == 0:
            status = "unbillable"
        elif cdr.call_flow == "sip_to_sip":
            status = "completed"  # No billing for internal calls
        else:
            status = "in_progress"  # Leave for billing service to process

        # Last-resort fallback: if Asterisk gave us 0 but the call answered,
        # compute wall-clock seconds from call_start. Slightly overcounts
        # because it includes ring time, but better than billing 0.
        if billsec == 0 and disposition == "ANSWERED":
            wall = session.execute(
                text("SELECT TIMESTAMPDIFF(SECOND, call_start, NOW()) AS s "
                     "FROM call_records WHERE uuid = :uuid"),
                {"uuid": cdr_uuid},
            ).scalar() or 0
            wall = max(0, int(wall))
            if wall > 0:
                logger.warning(
                    f"CDR {cdr_uuid}: ANSWEREDTIME/DIALEDTIME both 0; "
                    f"falling back to wall-clock {wall}s"
                )
                billsec = wall
                duration = max(duration, wall)
                # Re-evaluate billing status now that we have a non-zero billsec.
                if cdr.call_flow != "sip_to_sip":
                    status = "in_progress"

        # 5. Update CDR
        session.execute(
            text("""
                UPDATE call_records SET
                    call_end = NOW(),
                    duration = :duration,
                    billsec = :billsec,
                    disposition = :disposition,
                    hangup_cause = :hangup_cause,
                    status = :status
                WHERE uuid = :uuid
            """),
            {
                "uuid": cdr_uuid,
                "duration": duration,
                "billsec": billsec,
                "disposition": disposition,
                "hangup_cause": hangup_cause,
                "status": status,
            },
        )
        session.commit()

        logger.info(
            f"CDR {cdr_uuid}: {disposition} duration={duration}s "
            f"billsec={billsec}s status={status} "
            f"(ANSWEREDTIME={answered_str!r} DIALEDTIME={dialed_str!r})"
        )

        # 6. Trigger billing for answered trunk calls
        if status == "in_progress" and billsec > 0:
            try:
                from billing.tasks import rate_and_charge
                rate_and_charge.delay(cdr.id)
                logger.info(f"Queued billing for CDR {cdr.id} via Celery")
            except Exception as e:
                logger.warning(f"Celery dispatch failed for CDR {cdr.id}: {e} — billing sync")
                # Fallback: bill synchronously if Celery broker is unavailable
                try:
                    from billing.rating import RatingService
                    from billing.balance import BalanceService
                    from shared.config import get_settings
                    settings = get_settings()
                    r = redis_lib.from_url(settings.redis_url)
                    rating = RatingService(r)
                    balance = BalanceService()

                    result = rating.rate_call(cdr.id)
                    if result.get("status") == "rated":
                        balance.charge_call(cdr.id)
                    logger.info(f"Billed CDR {cdr.id} synchronously (Celery fallback)")
                except Exception as e2:
                    logger.error(f"Sync billing also failed for CDR {cdr.id}: {e2}")

        # 7. Clean up credit control metadata from Redis
        try:
            from shared.config import get_settings
            r = redis_lib.from_url(get_settings().redis_url)
            r.delete(f"rswitch:active_call:{cdr_uuid}")
        except Exception as e:
            logger.warning(f"Failed to clean up credit control key for {cdr_uuid}: {e}")

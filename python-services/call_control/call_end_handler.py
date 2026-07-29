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
from call_control.rtp_qos import read_rtp_qos

logger = logging.getLogger(__name__)

# Map Asterisk DIALSTATUS to CDR disposition. CANCEL stays distinct from
# NO ANSWER so the UI can show "Cancelled" (caller hung up before answer)
# vs "No Answer" (callee never picked up / rang out).
DIALSTATUS_MAP = {
    "ANSWER": "ANSWERED",
    "BUSY": "BUSY",
    "NOANSWER": "NO ANSWER",
    "CANCEL": "CANCEL",
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

        # Keep whether Asterisk actually REPORTED a value distinct from the
        # value itself: ANSWEREDTIME="0" (answered, sub-second talk) and
        # ANSWEREDTIME="" (no data) both parse to 0 but mean different things.
        answered_reported = answered_str.isdigit()
        billsec = int(answered_str) if answered_reported else 0
        # DIALEDTIME is the FULL Dial() duration (ring + talk) — it already
        # includes ANSWEREDTIME, so the call duration IS DIALEDTIME; ring-only
        # would be DIALEDTIME - ANSWEREDTIME. (Old bug: `billsec + DIALEDTIME`
        # double-counted the talk time, so duration exceeded the call's own
        # wall-clock window. Billing is unaffected — rating uses billsec.)
        duration = int(dialed_str) if dialed_str.isdigit() else 0
        if duration == 0 and duration_str.isdigit():
            duration = int(duration_str)
        if duration < billsec:  # a call can't be shorter than its talk time
            duration = billsec

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

        # Answered, but Asterisk reported no talk time. This is the 200 OK /
        # CANCEL glare case: the callee answers at the instant the caller gives
        # up, so the session establishes and is torn down within a second.
        #
        # The old code substituted wall-clock seconds from call_start here. That
        # is ring+talk, so it is ALWAYS wrong for a talk-time field, and it was
        # over-billing badly. Measured against the MMCL carrier CDR for
        # 2026-07-01..07: 364 such calls, every one SIP 200 with the egress leg
        # answered, carrier billable median 1s (min 0, max 25) — while we billed
        # 33-38s each. That is ~87 min/week charged to clients for ring time and
        # ~6x overstated trunk cost on this slice.
        #
        # These are provably NOT lost long calls: across 1,551 occurrences in 30
        # days the substituted wall-clock value ranged 3-47s with ZERO over 60s,
        # i.e. it tracks ring duration, not conversation length.
        #
        # Bill one increment instead. The carrier ceils fractional talk to 1s,
        # so 1s is what we are actually charged for these.
        if billsec == 0 and disposition == "ANSWERED":
            logger.warning(
                f"CDR {cdr_uuid}: answered with no talk time "
                f"(ANSWEREDTIME={answered_str!r} reported={answered_reported}, "
                f"DIALEDTIME={dialed_str!r}, cause={hangup_cause}); "
                f"billing minimum 1s (200 OK/CANCEL glare)"
            )
            billsec = 1
            # Keep DIALEDTIME as the call window so ring time stays visible;
            # only floor it if we have nothing better.
            duration = max(duration, billsec)
            # Re-evaluate billing status now that we have a non-zero billsec.
            if cdr.call_flow != "sip_to_sip":
                status = "in_progress"

        # Customer-leg RTP quality. Captured by [hangup-handler] into RTP_CUST_*
        # before it called us, so it rides along on this UPDATE rather than
        # costing a second AGI round-trip.
        cust = await read_rtp_qos(agi, "CUST")

        # 5. Update CDR
        session.execute(
            text("""
                UPDATE call_records SET
                    call_end = NOW(),
                    duration = :duration,
                    billsec = :billsec,
                    disposition = :disposition,
                    hangup_cause = :hangup_cause,
                    status = :status,
                    rtp_cust_rx_count = :cust_rx_count,
                    rtp_cust_tx_count = :cust_tx_count,
                    rtp_cust_rx_loss = :cust_rx_loss,
                    rtp_cust_tx_loss = :cust_tx_loss,
                    rtp_cust_rx_jitter = :cust_rx_jitter,
                    rtp_cust_rtt = :cust_rtt
                WHERE uuid = :uuid
            """),
            {
                "uuid": cdr_uuid,
                "duration": duration,
                "billsec": billsec,
                "disposition": disposition,
                "hangup_cause": hangup_cause,
                "status": status,
                "cust_rx_count": cust["rx_count"],
                "cust_tx_count": cust["tx_count"],
                "cust_rx_loss": cust["rx_loss"],
                "cust_tx_loss": cust["tx_loss"],
                "cust_rx_jitter": cust["rx_jitter"],
                "cust_rtt": cust["rtt"],
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

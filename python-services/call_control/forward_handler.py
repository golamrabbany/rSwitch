"""
Forward Call Handler — creates CDR for forwarded calls to mobile.

Called from extensions.conf [from-trunk] when CFNR/CFB triggers forwarding.
Only creates a CDR when forwarding to an external number (via trunk).
SIP-to-SIP forwarding doesn't need a separate CDR.

Channel variables expected:
  FORWARD_FROM     — original SIP account username
  FORWARD_DEST     — forward destination number
  FORWARD_DIAL_STRING — PJSIP dial string (already built by inbound handler)
  FORWARD_SIP_ID   — SIP account ID of the forwarder
  FORWARD_USER_ID  — user ID who owns the SIP account
  FORWARD_RESELLER_ID — reseller ID
"""

import logging
import uuid

from sqlalchemy import text
from sqlalchemy.orm import Session

from call_control.agi_protocol import AgiConnection

logger = logging.getLogger(__name__)


class ForwardCallHandler:
    """Creates a CDR for forwarded calls to external numbers."""

    async def handle(self, agi: AgiConnection, session: Session) -> None:
        try:
            await self._process(agi, session)
        except Exception as e:
            logger.error(f"Forward handler error: {e}", exc_info=True)
            # Don't block the forward — just skip CDR creation
            await agi.set_variable("FORWARD_CDR_UUID", "")

    async def _process(self, agi: AgiConnection, session: Session) -> None:
        forward_from = await agi.get_variable("FORWARD_FROM") or ""
        forward_dest = await agi.get_variable("FORWARD_DEST") or ""
        dial_string = await agi.get_variable("FORWARD_DIAL_STRING") or ""
        sip_id = await agi.get_variable("FORWARD_SIP_ID") or ""
        user_id = await agi.get_variable("FORWARD_USER_ID") or ""
        reseller_id = await agi.get_variable("FORWARD_RESELLER_ID") or ""
        caller_id = agi.get_caller_id()

        if not forward_from or not forward_dest:
            await agi.set_variable("FORWARD_CDR_UUID", "")
            return

        # Check if forwarding to SIP (internal) or trunk (external)
        is_trunk_forward = "@trunk-" in dial_string

        if not is_trunk_forward:
            # SIP-to-SIP forward — no billing CDR needed
            await agi.verbose(f"rSwitch: Forward {forward_from} -> SIP {forward_dest} (no billing)")
            await agi.set_variable("FORWARD_CDR_UUID", "")
            return

        # External forward via trunk — check balance and create CDR
        await agi.verbose(f"rSwitch: Forward {forward_from} -> Mobile {forward_dest} (billed)")

        # Check balance
        if user_id:
            user = session.execute(
                text("SELECT balance, credit_limit, billing_type FROM users WHERE id = :uid LIMIT 1"),
                {"uid": int(user_id)},
            ).first()

            if user and user.billing_type == 'prepaid':
                from decimal import Decimal
                available = Decimal(str(user.balance or 0)) + Decimal(str(user.credit_limit or 0))
                if available <= 0:
                    await agi.verbose(f"rSwitch: Forward blocked — insufficient balance for user {user_id}")
                    await agi.set_variable("FORWARD_CDR_UUID", "")
                    await agi.set_variable("FORWARD_DIAL_STRING", "")  # Clear to prevent dial
                    return

        # Extract trunk ID from dial string: PJSIP/number@trunk-both-{id}
        trunk_id = None
        import re
        trunk_match = re.search(r"trunk-(?:outgoing|both|incoming)-(\d+)", dial_string)
        if trunk_match:
            trunk_id = int(trunk_match.group(1))

        # Create CDR for the forwarded outbound leg
        cdr_uuid = str(uuid.uuid4())
        session.execute(
            text("""
                INSERT INTO call_records
                (uuid, sip_account_id, user_id, reseller_id, call_flow,
                 caller, callee, destination, forwarded_from, outgoing_trunk_id,
                 call_start, disposition, status, created_at)
                VALUES
                (:uuid, :sip_id, :user_id, :reseller_id, 'sip_to_trunk',
                 :caller, :callee, :destination, :fwd_from, :trunk_id,
                 NOW(), 'ANSWERED', 'in_progress', NOW())
            """),
            {
                "uuid": cdr_uuid,
                "sip_id": int(sip_id) if sip_id else None,
                "user_id": int(user_id) if user_id else None,
                "reseller_id": int(reseller_id) if reseller_id else None,
                "caller": caller_id,
                "callee": forward_dest,
                "destination": forward_dest,
                "fwd_from": forward_from,
                "trunk_id": trunk_id,
            },
        )
        session.commit()

        await agi.set_variable("FORWARD_CDR_UUID", cdr_uuid)
        await agi.verbose(f"rSwitch: Forward CDR {cdr_uuid} created — will be billed to user {user_id}")

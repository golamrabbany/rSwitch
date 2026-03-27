"""
Inbound Call Handler — Port of app/Services/Agi/InboundCallHandler.php

Handles Trunk-to-SIP incoming call routing.
Called from extensions.conf [from-trunk] context via FastAGI.

Flow:
1. Identify incoming trunk from PJSIP endpoint
2. Look up DID by called number
3. Route based on DID destination type (sip_account, ring_group, external)
4. Create CDR record
5. Set channel variables for dialplan
"""

import logging
import re
import uuid
from typing import Optional

from sqlalchemy import text
from sqlalchemy.orm import Session

from call_control.agi_protocol import AgiConnection
from call_control.transit_handler import TransitCallHandler

logger = logging.getLogger(__name__)

_transit = TransitCallHandler()


class InboundCallHandler:
    """Handles inbound call routing decisions via AGI."""

    async def handle(self, agi: AgiConnection, session: Session) -> None:
        try:
            await self._process(agi, session)
        except Exception as e:
            logger.error(f"Inbound handler error: {e}", exc_info=True)
            await agi.set_variable("ROUTE_ACTION", "REJECT")

    async def _process(self, agi: AgiConnection, session: Session) -> None:
        extension = agi.get_extension()  # Called DID number
        caller_id = agi.get_caller_id()

        await agi.verbose(f"rSwitch: Inbound {caller_id} -> DID {extension}")

        # 1. Identify incoming trunk
        trunk_endpoint = await agi.get_variable("TRUNK_ENDPOINT")
        trunk_id = None

        if trunk_endpoint:
            # Format: trunk-incoming-{id} or trunk-both-{id}
            match = re.search(r"trunk-(?:incoming|both)-(\d+)", trunk_endpoint)
            if match:
                trunk_id = int(match.group(1))

        if not trunk_id:
            await agi.verbose("rSwitch: Cannot identify incoming trunk")
            # Still try to route by DID — trunk might be unknown

        # 2. Look up DID
        # Try multiple number formats: exact, stripped +, with +
        did = None
        for number in [extension, extension.lstrip("+"), f"+{extension.lstrip('+')}"]:
            did = session.execute(
                text("""
                    SELECT d.id, d.number, d.destination_type, d.destination_number,
                           d.destination_id as sip_account_id,
                           d.destination_id as ring_group_id,
                           d.assigned_to_user_id as user_id,
                           s.username as sip_username,
                           u.parent_id as reseller_id
                    FROM dids d
                    LEFT JOIN sip_accounts s ON d.destination_id = s.id AND d.destination_type = 'sip_account'
                    LEFT JOIN users u ON d.assigned_to_user_id = u.id
                    WHERE d.number = :number AND d.status = 'active'
                    LIMIT 1
                """),
                {"number": number},
            ).first()
            if did:
                break

        if not did:
            # No DID match — try transit routing (trunk-to-trunk)
            if trunk_id:
                routed = await _transit.handle(agi, session, trunk_id, caller_id, extension)
                if routed:
                    return  # Transit call routed successfully

            await agi.verbose(f"rSwitch: DID {extension} not found, no transit route")
            await agi.set_variable("ROUTE_ACTION", "REJECT")
            await agi.set_variable("ROUTE_REJECT_REASON", "no_did")
            return

        # 3. Route based on destination type
        dial_string = ""
        dial_timeout = "60"

        if did.destination_type == "sip_account" and did.sip_username:
            # Route to SIP account
            dial_string = f"PJSIP/{did.sip_username}"
            await agi.verbose(f"rSwitch: DID {extension} -> SIP {did.sip_username}")

        elif did.destination_type == "ring_group" and did.ring_group_id:
            # Route to ring group — build dial string for all members
            members = session.execute(
                text("""
                    SELECT s.username
                    FROM ring_group_members rgm
                    JOIN sip_accounts s ON rgm.sip_account_id = s.id
                    WHERE rgm.ring_group_id = :rg_id AND s.status = 'active'
                    ORDER BY rgm.sort_order
                """),
                {"rg_id": did.ring_group_id},
            ).fetchall()

            if members:
                dial_string = "&".join(f"PJSIP/{m.username}" for m in members)
                await agi.verbose(f"rSwitch: DID {extension} -> Ring Group ({len(members)} members)")
            else:
                await agi.verbose("rSwitch: Ring group has no active members")
                await agi.set_variable("ROUTE_ACTION", "REJECT")
                return

        elif did.destination_type == "external" and did.destination_number:
            # Forward to external number via trunk
            dial_string = f"PJSIP/{did.destination_number}@trunk-outgoing-1"
            await agi.verbose(f"rSwitch: DID {extension} -> External {did.destination_number}")

        else:
            await agi.verbose(f"rSwitch: DID {extension} has no valid destination")
            await agi.set_variable("ROUTE_ACTION", "REJECT")
            return

        # 4. Create CDR
        cdr_uuid = str(uuid.uuid4())
        session.execute(
            text("""
                INSERT INTO call_records
                (uuid, user_id, reseller_id, call_flow, caller, callee,
                 incoming_trunk_id, did_id, sip_account_id,
                 call_start, disposition, status, created_at)
                VALUES
                (:uuid, :user_id, :reseller_id, 'trunk_to_sip', :caller, :callee,
                 :trunk_id, :did_id, :sip_account_id,
                 NOW(), 'ANSWERED', 'in_progress', NOW())
            """),
            {
                "uuid": cdr_uuid,
                "user_id": did.user_id,
                "reseller_id": did.reseller_id,
                "caller": caller_id,
                "callee": extension,
                "trunk_id": trunk_id,
                "did_id": did.id,
                "sip_account_id": did.sip_account_id,
            },
        )
        session.commit()

        # 5. Set channel variables
        await agi.set_variable("ROUTE_ACTION", "DIAL")
        await agi.set_variable("ROUTE_DIAL_STRING", dial_string)
        await agi.set_variable("ROUTE_DIAL_TIMEOUT", dial_timeout)
        await agi.set_variable("CDR_UUID", cdr_uuid)

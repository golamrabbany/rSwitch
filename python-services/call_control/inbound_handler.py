"""
Inbound Call Handler — Port of app/Services/Agi/InboundCallHandler.php

Handles Trunk-to-SIP incoming call routing.
Called from extensions.conf [from-trunk] context via FastAGI.

Flow:
1. Identify incoming trunk from PJSIP endpoint
2. Look up DID by called number
3. Route based on DID destination type (sip_account, ring_group, external)
4. Handle call forwarding (CFU/CFNR/CFB)
5. Create CDR record
6. Set channel variables for dialplan
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

        # 2. Direct SIP account match — if called number is a registered SIP account, ring it
        sip_target = None
        for number in [extension, extension.lstrip("+"), f"+{extension.lstrip('+')}"]:
            sip_target = session.execute(
                text("""
                    SELECT s.id, s.username, s.user_id, s.max_channels,
                           s.call_forward_enabled, s.call_forward_type,
                           s.call_forward_dest_type, s.call_forward_destination,
                           s.call_forward_timeout,
                           u.parent_id as reseller_id
                    FROM sip_accounts s
                    JOIN users u ON s.user_id = u.id
                    WHERE s.username = :ext AND s.status = 'active'
                    LIMIT 1
                """),
                {"ext": number},
            ).first()
            if sip_target:
                break

        if sip_target:
            # Handle call forwarding
            forward_result = await self._handle_forwarding(
                agi, session, sip_target, caller_id, extension, trunk_id
            )
            if forward_result:
                return  # Forwarding handled everything

            # Normal dial (no forwarding or CFNR/CFB — dial first, forward on fail)
            dial_string = f"PJSIP/{sip_target.username}"
            dial_timeout = str(sip_target.call_forward_timeout or 60) if sip_target.call_forward_enabled else "60"

            await agi.verbose(f"rSwitch: Inbound -> SIP {sip_target.username} (direct match)")

            # Create CDR
            cdr_uuid = str(uuid.uuid4())
            session.execute(
                text("""
                    INSERT INTO call_records
                    (uuid, user_id, reseller_id, call_flow, caller, callee,
                     incoming_trunk_id, sip_account_id,
                     call_start, disposition, status, created_at)
                    VALUES
                    (:uuid, :user_id, :reseller_id, 'trunk_to_sip',
                     :caller, :callee, :trunk_id, :sip_id,
                     NOW(), 'ANSWERED', 'in_progress', NOW())
                """),
                {
                    "uuid": cdr_uuid,
                    "user_id": sip_target.user_id,
                    "reseller_id": sip_target.reseller_id,
                    "caller": caller_id,
                    "callee": extension,
                    "trunk_id": trunk_id,
                    "sip_id": sip_target.id,
                },
            )
            session.commit()

            # Set forward variables for dialplan (CFNR/CFB handled after dial)
            await agi.set_variable("ROUTE_ACTION", "DIAL")
            await agi.set_variable("ROUTE_DIAL_STRING", dial_string)
            await agi.set_variable("ROUTE_DIAL_TIMEOUT", dial_timeout)
            await agi.set_variable("CDR_UUID", cdr_uuid)

            if sip_target.call_forward_enabled and sip_target.call_forward_type in ('cfnr', 'cfb', 'cfnr_cfb'):
                fwd_dest_type = getattr(sip_target, 'call_forward_dest_type', 'number') or 'number'
                fwd_dest = sip_target.username if fwd_dest_type == 'route' else sip_target.call_forward_destination
                fwd_type = sip_target.call_forward_type
                # Build forward dial string
                fwd_dial = await self._build_forward_dial(session, fwd_dest, sip_target, force_trunk=(fwd_dest_type == 'route'))
                if fwd_dial:
                    await agi.set_variable("FORWARD_ENABLED", "1")
                    await agi.set_variable("FORWARD_TYPE", fwd_type)
                    await agi.set_variable("FORWARD_DIAL_STRING", fwd_dial)
                    await agi.set_variable("FORWARD_FROM", sip_target.username)
                    await agi.set_variable("FORWARD_DEST", fwd_dest)
                    await agi.set_variable("FORWARD_SIP_ID", str(sip_target.id))
                    await agi.set_variable("FORWARD_USER_ID", str(sip_target.user_id))
                    await agi.set_variable("FORWARD_RESELLER_ID", str(sip_target.reseller_id or ''))
                    await agi.verbose(f"rSwitch: Forward set ({fwd_type}) -> {fwd_dest}")
            return

        # 3. Look up DID
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
                    return

            await agi.verbose(f"rSwitch: DID {extension} not found, no SIP match, no transit route")
            await agi.set_variable("ROUTE_ACTION", "REJECT")
            await agi.set_variable("ROUTE_REJECT_REASON", "no_did")
            return

        # 4. Route based on DID destination type
        dial_string = ""
        dial_timeout = "60"
        sip_fwd = None

        if did.destination_type == "sip_account" and did.sip_username:
            # Check forwarding for DID-routed SIP accounts
            sip_fwd = session.execute(
                text("""
                    SELECT s.id, s.username, s.user_id, s.max_channels,
                           s.call_forward_enabled, s.call_forward_type,
                           s.call_forward_dest_type, s.call_forward_destination,
                           s.call_forward_timeout,
                           u.parent_id as reseller_id
                    FROM sip_accounts s
                    JOIN users u ON s.user_id = u.id
                    WHERE s.id = :sid AND s.status = 'active'
                    LIMIT 1
                """),
                {"sid": did.sip_account_id},
            ).first()

            if sip_fwd and sip_fwd.call_forward_enabled and sip_fwd.call_forward_type == 'cfu':
                # CFU — forward immediately without ringing
                forward_result = await self._handle_forwarding(
                    agi, session, sip_fwd, caller_id, extension, trunk_id
                )
                if forward_result:
                    return

            dial_string = f"PJSIP/{did.sip_username}"
            dial_timeout = str(sip_fwd.call_forward_timeout or 60) if sip_fwd and sip_fwd.call_forward_enabled else "60"
            await agi.verbose(f"rSwitch: DID {extension} -> SIP {did.sip_username}")

        elif did.destination_type == "ring_group" and did.ring_group_id:
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
            dial_string = f"PJSIP/{did.destination_number}@trunk-outgoing-1"
            await agi.verbose(f"rSwitch: DID {extension} -> External {did.destination_number}")

        else:
            await agi.verbose(f"rSwitch: DID {extension} has no valid destination")
            await agi.set_variable("ROUTE_ACTION", "REJECT")
            return

        # 5. Create CDR
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

        # 6. Set channel variables
        await agi.set_variable("ROUTE_ACTION", "DIAL")
        await agi.set_variable("ROUTE_DIAL_STRING", dial_string)
        await agi.set_variable("ROUTE_DIAL_TIMEOUT", dial_timeout)
        await agi.set_variable("CDR_UUID", cdr_uuid)

        # Set forward variables for DID→SIP with CFNR/CFB
        if sip_fwd and sip_fwd.call_forward_enabled and sip_fwd.call_forward_type in ('cfnr', 'cfb', 'cfnr_cfb'):
            did_fwd_dest_type = getattr(sip_fwd, 'call_forward_dest_type', 'number') or 'number'
            did_fwd_dest = sip_fwd.username if did_fwd_dest_type == 'route' else sip_fwd.call_forward_destination
            fwd_dial = await self._build_forward_dial(session, did_fwd_dest, sip_fwd, force_trunk=(did_fwd_dest_type == 'route'))
            if fwd_dial:
                await agi.set_variable("FORWARD_ENABLED", "1")
                await agi.set_variable("FORWARD_TYPE", sip_fwd.call_forward_type)
                await agi.set_variable("FORWARD_DIAL_STRING", fwd_dial)
                await agi.set_variable("FORWARD_FROM", sip_fwd.username)
                await agi.set_variable("FORWARD_DEST", did_fwd_dest)
                await agi.set_variable("FORWARD_SIP_ID", str(sip_fwd.id))
                await agi.set_variable("FORWARD_USER_ID", str(sip_fwd.user_id))
                await agi.set_variable("FORWARD_RESELLER_ID", str(sip_fwd.reseller_id or ''))

    async def _handle_forwarding(self, agi, session, sip, caller_id, extension, trunk_id):
        """Handle CFU (unconditional forward). Returns True if handled."""
        if not sip.call_forward_enabled:
            return False

        if sip.call_forward_type != 'cfu':
            return False  # CFNR/CFB handled by dialplan after dial attempt

        dest_type = getattr(sip, 'call_forward_dest_type', 'number') or 'number'
        fwd_dest = sip.username if dest_type == 'route' else sip.call_forward_destination
        if not fwd_dest:
            return False

        await agi.verbose(f"rSwitch: CFU {sip.username} -> {fwd_dest} (via {dest_type})")

        fwd_dial = await self._build_forward_dial(session, fwd_dest, sip, force_trunk=(dest_type == 'route'))
        if not fwd_dial:
            await agi.verbose(f"rSwitch: Cannot build forward dial for {fwd_dest}")
            return False

        # Create CDR for inbound leg (no answer — forwarded)
        cdr_uuid = str(uuid.uuid4())
        session.execute(
            text("""
                INSERT INTO call_records
                (uuid, user_id, reseller_id, call_flow, caller, callee,
                 forwarded_from, incoming_trunk_id, sip_account_id,
                 call_start, disposition, status, created_at)
                VALUES
                (:uuid, :user_id, :reseller_id, 'trunk_to_sip',
                 :caller, :callee, :fwd_from, :trunk_id, :sip_id,
                 NOW(), 'ANSWERED', 'in_progress', NOW())
            """),
            {
                "uuid": cdr_uuid,
                "user_id": sip.user_id,
                "reseller_id": sip.reseller_id,
                "caller": caller_id,
                "callee": fwd_dest,
                "fwd_from": sip.username,
                "trunk_id": trunk_id,
                "sip_id": sip.id,
            },
        )
        session.commit()

        await agi.set_variable("ROUTE_ACTION", "DIAL")
        await agi.set_variable("ROUTE_DIAL_STRING", fwd_dial)
        await agi.set_variable("ROUTE_DIAL_TIMEOUT", "60")
        await agi.set_variable("CDR_UUID", cdr_uuid)
        return True

    async def _build_forward_dial(self, session, destination, sip, force_trunk=False):
        """Build dial string for forward destination — SIP or trunk.
        force_trunk=True skips SIP check (used for 'route' dest type)."""
        if not destination:
            return None

        # Check if destination is a registered SIP account (skip for route mode)
        if not force_trunk:
            sip_dest = session.execute(
                text("SELECT username FROM sip_accounts WHERE username = :dest AND status = 'active' LIMIT 1"),
                {"dest": destination},
            ).first()

            if sip_dest:
                return f"PJSIP/{sip_dest.username}"

        # External number — find a route via trunk
        route = session.execute(
            text("""
                SELECT tr.id, tr.prefix, tr.remove_prefix, tr.add_prefix,
                       tr.mnp_enabled, t.id as tid, t.direction as trunk_direction
                FROM trunk_routes tr
                JOIN trunks t ON tr.trunk_id = t.id
                WHERE tr.status = 'active'
                AND t.status = 'active'
                AND t.direction IN ('outgoing', 'both')
                AND :dest LIKE CONCAT(tr.prefix, '%')
                ORDER BY LENGTH(tr.prefix) DESC, tr.priority ASC
                LIMIT 1
            """),
            {"dest": destination},
        ).first()

        if not route:
            return None

        # Apply dial manipulation
        dial_number = destination
        if route.remove_prefix and dial_number.startswith(route.remove_prefix):
            dial_number = dial_number[len(route.remove_prefix):]
        if route.add_prefix:
            dial_number = route.add_prefix + dial_number

        # Apply MNP
        if route.mnp_enabled:
            from call_control.outbound_handler import _apply_bd_mnp
            dial_number = _apply_bd_mnp(dial_number)

        trunk_endpoint = f"trunk-{route.trunk_direction}-{route.tid}"
        return f"PJSIP/{dial_number}@{trunk_endpoint}"

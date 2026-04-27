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

from sqlalchemy import bindparam, text

from call_control.agi_protocol import AgiConnection
from call_control.transit_handler import TransitCallHandler
from shared.database import db_thread

logger = logging.getLogger(__name__)

_transit = TransitCallHandler()

# BD MNP codes for reverse lookup
BD_MNP_CODES = {'71', '91', '51', '81'}


def _reverse_bd_mnp(number: str) -> str:
    """Reverse BD MNP format to clean international format.
    880711714101351 (15 digits, MNP) → 8801714101351 (13 digits, clean)
    Non-BD or non-MNP numbers pass through unchanged."""
    n = number.lstrip('+')
    if n.startswith('00'):
        n = n[2:]
    if len(n) == 15 and n.startswith('880') and n[3:5] in BD_MNP_CODES:
        return '880' + n[5:]
    return number


def _extension_variants(extension: str) -> list[str]:
    """Return distinct number variants to try for SIP/DID matching:
    raw, stripped of leading +, and with explicit + prefix."""
    bare = extension.lstrip("+")
    plus = f"+{bare}"
    seen, out = set(), []
    for v in (extension, bare, plus):
        if v and v not in seen:
            seen.add(v)
            out.append(v)
    return out


_SIP_BY_USERNAME = text("""
    SELECT s.id, s.username, s.user_id, s.max_channels,
           s.allow_recording,
           s.call_forward_enabled, s.call_forward_type,
           s.call_forward_dest_type, s.call_forward_destination,
           s.call_forward_timeout,
           u.parent_id as reseller_id
    FROM sip_accounts s
    JOIN users u ON s.user_id = u.id
    WHERE s.username IN :unames AND s.status = 'active'
    LIMIT 1
""").bindparams(bindparam("unames", expanding=True))


_DID_BY_NUMBER = text("""
    SELECT d.id, d.number, d.destination_type, d.destination_number,
           d.destination_id as sip_account_id,
           d.destination_id as ring_group_id,
           d.assigned_to_user_id as user_id,
           s.username as sip_username,
           u.parent_id as reseller_id
    FROM dids d
    LEFT JOIN sip_accounts s ON d.destination_id = s.id AND d.destination_type = 'sip_account'
    LEFT JOIN users u ON d.assigned_to_user_id = u.id
    WHERE d.number IN :numbers AND d.status = 'active'
    LIMIT 1
""").bindparams(bindparam("numbers", expanding=True))


def _select_sip_by_username(session, usernames: list[str]):
    """Direct SIP-account match. Folded from a 3-iter loop into one IN query."""
    return session.execute(_SIP_BY_USERNAME, {"unames": list(usernames)}).first()


def _select_did_by_number(session, numbers: list[str]):
    return session.execute(_DID_BY_NUMBER, {"numbers": list(numbers)}).first()


def _select_sip_by_id(session, sip_id: int):
    return session.execute(
        text("""
            SELECT s.id, s.username, s.user_id, s.max_channels,
                   s.allow_recording,
                   s.call_forward_enabled, s.call_forward_type,
                   s.call_forward_dest_type, s.call_forward_destination,
                   s.call_forward_timeout,
                   u.parent_id as reseller_id
            FROM sip_accounts s
            JOIN users u ON s.user_id = u.id
            WHERE s.id = :sid AND s.status = 'active'
            LIMIT 1
        """),
        {"sid": sip_id},
    ).first()


def _select_ring_group_members(session, rg_id: int):
    return session.execute(
        text("""
            SELECT s.username
            FROM ring_group_members rgm
            JOIN sip_accounts s ON rgm.sip_account_id = s.id
            WHERE rgm.ring_group_id = :rg_id AND s.status = 'active'
            ORDER BY rgm.sort_order
        """),
        {"rg_id": rg_id},
    ).fetchall()


def _select_sip_for_forward_dest(session, destination: str):
    return session.execute(
        text("SELECT username FROM sip_accounts WHERE username = :dest AND status = 'active' LIMIT 1"),
        {"dest": destination},
    ).first()


def _select_forward_route(session, destination: str):
    return session.execute(
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


def _insert_inbound_cdr_direct(session, payload: dict) -> None:
    session.execute(
        text("""
            INSERT INTO call_records
            (uuid, user_id, reseller_id, call_flow, caller, caller_id, callee,
             incoming_trunk_id, sip_account_id,
             call_start, disposition, status, created_at)
            VALUES
            (:uuid, :user_id, :reseller_id, 'trunk_to_sip',
             :caller, :caller_id, :callee, :trunk_id, :sip_id,
             NOW(), 'ANSWERED', 'in_progress', NOW())
        """),
        payload,
    )


def _insert_inbound_cdr_did(session, payload: dict) -> None:
    session.execute(
        text("""
            INSERT INTO call_records
            (uuid, user_id, reseller_id, call_flow, caller, caller_id, callee,
             incoming_trunk_id, did_id, sip_account_id,
             call_start, disposition, status, created_at)
            VALUES
            (:uuid, :user_id, :reseller_id, 'trunk_to_sip', :caller, :caller_id, :callee,
             :trunk_id, :did_id, :sip_account_id,
             NOW(), 'ANSWERED', 'in_progress', NOW())
        """),
        payload,
    )


def _insert_forward_cdr(session, payload: dict) -> None:
    session.execute(
        text("""
            INSERT INTO call_records
            (uuid, user_id, reseller_id, call_flow, caller, caller_id, callee,
             forwarded_from, incoming_trunk_id, sip_account_id,
             call_start, disposition, status, created_at)
            VALUES
            (:uuid, :user_id, :reseller_id, 'trunk_to_sip',
             :caller, :caller_id, :callee, :fwd_from, :trunk_id, :sip_id,
             NOW(), 'ANSWERED', 'in_progress', NOW())
        """),
        payload,
    )


class InboundCallHandler:
    """Handles inbound call routing decisions via AGI.

    Sync DB calls are off-loaded via shared.database.db_thread so the
    asyncio event loop stays responsive under load.
    """

    async def handle(self, agi: AgiConnection) -> None:
        try:
            await self._process(agi)
        except Exception as e:
            logger.error(f"Inbound handler error: {e}", exc_info=True)
            await agi.set_variable("ROUTE_ACTION", "REJECT")

    async def _process(self, agi: AgiConnection) -> None:
        extension = agi.get_extension()  # Called DID number
        raw_caller = agi.get_caller_id()  # Original from trunk (may be MNP)
        clean_caller = _reverse_bd_mnp(raw_caller)  # Reversed for display

        # Set clean caller ID on channel for phone display
        if clean_caller != raw_caller:
            await agi.set_variable("CALLERID(num)", clean_caller)
            await agi.verbose(f"rSwitch: MNP reverse {raw_caller} -> {clean_caller}")

        caller_id = raw_caller  # Keep raw in caller_id variable for CDR

        await agi.verbose(f"rSwitch: Inbound {raw_caller} -> DID {extension}")

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

        # 2. Direct SIP account match (single query across number variants)
        variants = _extension_variants(extension)
        sip_target = await db_thread(lambda s: _select_sip_by_username(s, variants))

        if sip_target:
            # Handle call forwarding
            forward_result = await self._handle_forwarding(
                agi, sip_target, caller_id, extension, trunk_id,
                raw_caller=raw_caller, clean_caller=clean_caller
            )
            if forward_result:
                return  # Forwarding handled everything

            # Normal dial (no forwarding or CFNR/CFB — dial first, forward on fail)
            dial_string = f"PJSIP/{sip_target.username}"
            dial_timeout = str(sip_target.call_forward_timeout or 60) if sip_target.call_forward_enabled else "60"

            await agi.verbose(f"rSwitch: Inbound -> SIP {sip_target.username} (direct match)")

            # Create CDR
            cdr_uuid = str(uuid.uuid4())
            payload = {
                "uuid": cdr_uuid,
                "user_id": sip_target.user_id,
                "reseller_id": sip_target.reseller_id,
                "caller": raw_caller,
                "caller_id": clean_caller,
                "callee": extension,
                "trunk_id": trunk_id,
                "sip_id": sip_target.id,
            }
            await db_thread(lambda s: _insert_inbound_cdr_direct(s, payload))

            # Set variables for dialplan (CFNR/CFB handled after dial)
            await agi.set_variable("ROUTE_ACTION", "DIAL")
            await agi.set_variable("ROUTE_DIAL_STRING", dial_string)
            await agi.set_variable("ROUTE_DIAL_TIMEOUT", dial_timeout)
            await agi.set_variable("CDR_UUID", cdr_uuid)
            await agi.set_variable("RECORD_CALL", "1" if sip_target.allow_recording else "0")

            if sip_target.call_forward_enabled and sip_target.call_forward_type in ('cfnr', 'cfb', 'cfnr_cfb'):
                fwd_dest_type = getattr(sip_target, 'call_forward_dest_type', 'number') or 'number'
                fwd_dest = sip_target.username if fwd_dest_type == 'route' else sip_target.call_forward_destination
                fwd_type = sip_target.call_forward_type
                fwd_dial = await self._build_forward_dial(fwd_dest, sip_target, force_trunk=(fwd_dest_type == 'route'))
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

        # 3. Look up DID (single query across number variants)
        did = await db_thread(lambda s: _select_did_by_number(s, variants))

        if not did:
            # No DID match — try transit routing (trunk-to-trunk)
            if trunk_id:
                routed = await _transit.handle(agi, trunk_id, caller_id, extension)
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
            sip_fwd = await db_thread(lambda s: _select_sip_by_id(s, did.sip_account_id))

            if sip_fwd and sip_fwd.call_forward_enabled and sip_fwd.call_forward_type == 'cfu':
                # CFU — forward immediately without ringing
                forward_result = await self._handle_forwarding(
                    agi, sip_fwd, caller_id, extension, trunk_id,
                    raw_caller=raw_caller, clean_caller=clean_caller
                )
                if forward_result:
                    return

            dial_string = f"PJSIP/{did.sip_username}"
            dial_timeout = str(sip_fwd.call_forward_timeout or 60) if sip_fwd and sip_fwd.call_forward_enabled else "60"
            await agi.verbose(f"rSwitch: DID {extension} -> SIP {did.sip_username}")

        elif did.destination_type == "ring_group" and did.ring_group_id:
            members = await db_thread(lambda s: _select_ring_group_members(s, did.ring_group_id))

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
        payload = {
            "uuid": cdr_uuid,
            "user_id": did.user_id,
            "reseller_id": did.reseller_id,
            "caller": raw_caller,
            "caller_id": clean_caller,
            "callee": extension,
            "trunk_id": trunk_id,
            "did_id": did.id,
            "sip_account_id": did.sip_account_id,
        }
        await db_thread(lambda s: _insert_inbound_cdr_did(s, payload))

        # 6. Set channel variables
        await agi.set_variable("ROUTE_ACTION", "DIAL")
        await agi.set_variable("ROUTE_DIAL_STRING", dial_string)
        await agi.set_variable("ROUTE_DIAL_TIMEOUT", dial_timeout)
        await agi.set_variable("CDR_UUID", cdr_uuid)
        await agi.set_variable("RECORD_CALL", "1" if sip_fwd and sip_fwd.allow_recording else "0")

        # Set forward variables for DID→SIP with CFNR/CFB
        if sip_fwd and sip_fwd.call_forward_enabled and sip_fwd.call_forward_type in ('cfnr', 'cfb', 'cfnr_cfb'):
            did_fwd_dest_type = getattr(sip_fwd, 'call_forward_dest_type', 'number') or 'number'
            did_fwd_dest = sip_fwd.username if did_fwd_dest_type == 'route' else sip_fwd.call_forward_destination
            fwd_dial = await self._build_forward_dial(did_fwd_dest, sip_fwd, force_trunk=(did_fwd_dest_type == 'route'))
            if fwd_dial:
                await agi.set_variable("FORWARD_ENABLED", "1")
                await agi.set_variable("FORWARD_TYPE", sip_fwd.call_forward_type)
                await agi.set_variable("FORWARD_DIAL_STRING", fwd_dial)
                await agi.set_variable("FORWARD_FROM", sip_fwd.username)
                await agi.set_variable("FORWARD_DEST", did_fwd_dest)
                await agi.set_variable("FORWARD_SIP_ID", str(sip_fwd.id))
                await agi.set_variable("FORWARD_USER_ID", str(sip_fwd.user_id))
                await agi.set_variable("FORWARD_RESELLER_ID", str(sip_fwd.reseller_id or ''))

    async def _handle_forwarding(self, agi, sip, caller_id, extension, trunk_id, raw_caller=None, clean_caller=None):
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

        fwd_dial = await self._build_forward_dial(fwd_dest, sip, force_trunk=(dest_type == 'route'))
        if not fwd_dial:
            await agi.verbose(f"rSwitch: Cannot build forward dial for {fwd_dest}")
            return False

        # Create CDR for inbound leg (no answer — forwarded)
        cdr_uuid = str(uuid.uuid4())
        payload = {
            "uuid": cdr_uuid,
            "user_id": sip.user_id,
            "reseller_id": sip.reseller_id,
            "caller": raw_caller or caller_id,
            "caller_id": clean_caller or caller_id,
            "callee": fwd_dest,
            "fwd_from": sip.username,
            "trunk_id": trunk_id,
            "sip_id": sip.id,
        }
        await db_thread(lambda s: _insert_forward_cdr(s, payload))

        await agi.set_variable("ROUTE_ACTION", "DIAL")
        await agi.set_variable("ROUTE_DIAL_STRING", fwd_dial)
        await agi.set_variable("ROUTE_DIAL_TIMEOUT", "60")
        await agi.set_variable("CDR_UUID", cdr_uuid)
        return True

    async def _build_forward_dial(self, destination, sip, force_trunk=False):
        """Build dial string for forward destination — SIP or trunk.
        force_trunk=True skips SIP check (used for 'route' dest type)."""
        if not destination:
            return None

        # Check if destination is a registered SIP account (skip for route mode)
        if not force_trunk:
            sip_dest = await db_thread(lambda s: _select_sip_for_forward_dest(s, destination))
            if sip_dest:
                return f"PJSIP/{sip_dest.username}"

        # External number — find a route via trunk
        route = await db_thread(lambda s: _select_forward_route(s, destination))

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

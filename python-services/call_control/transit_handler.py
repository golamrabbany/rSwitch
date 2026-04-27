"""
Transit Call Handler — Trunk-to-Trunk routing.

Handles calls that arrive on an incoming trunk but don't match any DID.
Routes to an outgoing trunk based on trunk routing rules.

Flow:
1. Called from InboundCallHandler when no DID found
2. Look up trunk routes for the destination (same as outbound routing)
3. Apply dial manipulation (prefix add/remove)
4. Create CDR with call_flow='trunk_to_trunk'
5. Set channel variables for dialplan
"""

import logging
import uuid as uuid_lib

from sqlalchemy import text

from call_control.agi_protocol import AgiConnection
from shared.database import db_thread

logger = logging.getLogger(__name__)


def _select_transit_routes(session, dest: str, incoming_trunk_id: int):
    return session.execute(
        text("""
            SELECT tr.id, tr.prefix, tr.priority, tr.weight, tr.trunk_id,
                   tr.remove_prefix, tr.add_prefix, t.tech_prefix,
                   t.name as trunk_name, t.id as tid,
                   t.cli_mode, t.cli_override_number, t.max_channels as trunk_max_channels
            FROM trunk_routes tr
            JOIN trunks t ON tr.trunk_id = t.id
            WHERE tr.status = 'active'
            AND t.status = 'active'
            AND t.direction IN ('outgoing', 'both')
            AND t.health_status != 'down'
            AND t.id != :incoming_trunk_id
            AND :dest LIKE CONCAT(tr.prefix, '%')
            ORDER BY LENGTH(tr.prefix) DESC, tr.priority ASC, tr.weight DESC
        """),
        {"dest": dest, "incoming_trunk_id": incoming_trunk_id},
    ).fetchall()


def _insert_transit_cdr(session, payload: dict) -> None:
    session.execute(
        text("""
            INSERT INTO call_records
            (uuid, user_id, call_flow, caller, callee, destination,
             incoming_trunk_id, outgoing_trunk_id,
             call_start, disposition, status, created_at)
            VALUES
            (:uuid, 0, 'trunk_to_trunk', :caller, :callee, :destination,
             :incoming_trunk_id, :outgoing_trunk_id,
             NOW(), 'ANSWERED', 'in_progress', NOW())
        """),
        payload,
    )


class TransitCallHandler:
    """Handles trunk-to-trunk transit call routing.

    Sync DB calls are off-loaded via shared.database.db_thread.
    """

    async def handle(
        self,
        agi: AgiConnection,
        incoming_trunk_id: int,
        caller_id: str,
        extension: str,
    ) -> bool:
        """
        Try to route as transit call. Returns True if routed, False if no route found.
        Called by InboundCallHandler when no DID matches.
        """
        try:
            return await self._process(agi, incoming_trunk_id, caller_id, extension)
        except Exception as e:
            logger.error(f"Transit handler error: {e}", exc_info=True)
            return False

    async def _process(
        self,
        agi: AgiConnection,
        incoming_trunk_id: int,
        caller_id: str,
        extension: str,
    ) -> bool:
        await agi.verbose(f"rSwitch: Transit check {caller_id} -> {extension} (incoming trunk {incoming_trunk_id})")

        # 1. Find outgoing trunk route for the destination
        routes = await db_thread(lambda s: _select_transit_routes(s, extension, incoming_trunk_id))

        if not routes:
            await agi.verbose(f"rSwitch: No transit route for {extension}")
            return False

        primary = routes[0]

        # 2. Apply dial manipulation
        dial_number = extension

        if primary.remove_prefix and dial_number.startswith(primary.remove_prefix):
            dial_number = dial_number[len(primary.remove_prefix):]

        if primary.add_prefix:
            dial_number = primary.add_prefix + dial_number

        if primary.tech_prefix:
            dial_number = primary.tech_prefix + dial_number

        # 3. Build dial string
        trunk_endpoint = f"trunk-outgoing-{primary.tid}"
        dial_string = f"PJSIP/{dial_number}@{trunk_endpoint}"

        # Failover trunk
        failover_string = ""
        if len(routes) > 1:
            failover = routes[1]
            fo_number = extension
            if failover.remove_prefix and fo_number.startswith(failover.remove_prefix):
                fo_number = fo_number[len(failover.remove_prefix):]
            if failover.add_prefix:
                fo_number = failover.add_prefix + fo_number
            if failover.tech_prefix:
                fo_number = failover.tech_prefix + fo_number
            fo_endpoint = f"trunk-outgoing-{failover.tid}"
            failover_string = f"PJSIP/{fo_number}@{fo_endpoint}"

        # 4. Determine CLI
        cli_num = caller_id
        if primary.cli_mode == "override" and primary.cli_override_number:
            cli_num = primary.cli_override_number
        elif primary.cli_mode == "hide":
            cli_num = extension

        # 5. Create CDR (off-loop, commits via get_session() ctx manager)
        cdr_uuid = str(uuid_lib.uuid4())
        payload = {
            "uuid": cdr_uuid,
            "caller": caller_id,
            "callee": extension,
            "destination": dial_number,
            "incoming_trunk_id": incoming_trunk_id,
            "outgoing_trunk_id": primary.tid,
        }
        await db_thread(lambda s: _insert_transit_cdr(s, payload))

        # 6. Set channel variables
        await agi.set_variable("ROUTE_ACTION", "DIAL")
        await agi.set_variable("ROUTE_DIAL_STRING", dial_string)
        await agi.set_variable("ROUTE_FAILOVER", failover_string)
        await agi.set_variable("ROUTE_DIAL_TIMEOUT", "90")
        await agi.set_variable("ROUTE_CLI_NUM", cli_num)
        await agi.set_variable("CDR_UUID", cdr_uuid)

        # Max duration for transit calls — 4 hours cap (no balance-based limit)
        await agi.set_variable("RSWITCH_MAX_DURATION", "14400")
        await agi.verbose("rSwitch: Transit max_duration=14400s (4h cap)")

        await agi.verbose(
            f"rSwitch: Transit {extension} via {primary.trunk_name} "
            f"({dial_string}), incoming trunk={incoming_trunk_id}"
        )

        return True

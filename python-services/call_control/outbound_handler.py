"""
Outbound Call Handler — Port of app/Services/Agi/OutboundCallHandler.php

Handles SIP-to-Trunk and SIP-to-SIP call routing.
Called from extensions.conf [from-internal] context via FastAGI.

Flow:
1. Extract SIP account from channel name
2. Validate account and user (active, not suspended)
3. Check destination against blacklist/whitelist
4. Look up rate and validate balance
5. Check daily limits
6. Detect internal calls (SIP-to-SIP)
7. Select trunk with RouteSelectionService
8. Apply dial manipulation (prefix add/remove)
9. Set CLI (Caller ID)
10. Create CDR record
11. Set channel variables for dialplan
"""

import json
import logging
import os
import re
import time
import uuid
from datetime import datetime
from decimal import Decimal
from typing import Optional

import redis as redis_lib
from sqlalchemy import text, func
from sqlalchemy.orm import Session

from call_control.agi_protocol import AgiConnection

logger = logging.getLogger(__name__)

# Module-level Redis connection pool — reused across all calls (no TCP per call)
_redis_pool = None


# BD MNP operator prefix → route number
BD_MNP_MAP = {
    '13': '71',  # Grameenphone
    '14': '91',  # Banglalink
    '15': '51',  # Teletalk
    '16': '81',  # Airtel
    '17': '71',  # Grameenphone
    '18': '81',  # Robi
    '19': '91',  # Banglalink
}


def _weighted_select(routes):
    """Select a route using weighted random among same-priority candidates.
    Routes are already sorted by prefix length DESC, priority ASC, weight DESC.
    Pick from routes sharing the best (lowest) priority using weight as probability."""
    import random

    if len(routes) == 1:
        return routes[0]

    # Get the best priority (already sorted)
    best_priority = routes[0].priority

    # Filter candidates with same priority
    candidates = [r for r in routes if r.priority == best_priority]

    if len(candidates) == 1:
        return candidates[0]

    # Weighted random selection
    weights = [max(r.weight or 1, 1) for r in candidates]
    return random.choices(candidates, weights=weights, k=1)[0]


def _apply_bd_mnp(number: str) -> str:
    """Auto-convert BD number to MNP format: 880 + route_code + national_number.
    Non-BD numbers pass through unchanged."""
    n = number.lstrip('+')

    # Normalize to national (strip country code / leading zero)
    if n.startswith('00880'):
        national = n[5:]
    elif n.startswith('880'):
        national = n[3:]
    elif n.startswith('0'):
        national = n[1:]
    else:
        national = n

    # Validate: 10 digits, starts with BD operator prefix
    if len(national) != 10:
        return number

    op = national[:2]
    mnp_code = BD_MNP_MAP.get(op)
    if not mnp_code:
        return number  # Not BD mobile, passthrough

    return '880' + mnp_code + national


def _get_redis() -> redis_lib.Redis:
    """Get Redis client from shared connection pool (created once, reused)."""
    global _redis_pool
    if _redis_pool is None:
        from shared.config import get_settings
        redis_url = get_settings().redis_url
        _redis_pool = redis_lib.ConnectionPool.from_url(redis_url, max_connections=20)
    return redis_lib.Redis(connection_pool=_redis_pool)


class OutboundCallHandler:
    """Handles outbound call routing decisions via AGI."""

    async def handle(self, agi: AgiConnection, session: Session) -> None:
        try:
            await self._process(agi, session)
        except Exception as e:
            logger.error(f"Outbound handler error: {e}", exc_info=True)
            await agi.set_variable("ROUTE_ACTION", "REJECT")
            await agi.set_variable("ROUTE_REJECT_REASON", "internal_error")

    async def _process(self, agi: AgiConnection, session: Session) -> None:
        channel = agi.get_channel()
        extension = agi.get_extension()
        caller_id = agi.get_caller_id()

        # Get SIP device info
        src_ip_raw = await agi.get_variable("CHANNEL(pjsip,remote_addr)") or ""
        src_ip = src_ip_raw.split(":")[0] if src_ip_raw else ""  # Strip port
        user_agent = await agi.get_variable("CHANNEL(pjsip,useragent)") or ""

        await agi.verbose(f"rSwitch: Outbound {caller_id} -> {extension} (IP:{src_ip})")

        # 1. Extract SIP account username from PJSIP channel
        # Format: PJSIP/username-uniqueid
        match = re.match(r"PJSIP/([^-]+)", channel)
        if not match:
            await agi.verbose("rSwitch: Cannot extract SIP account from channel")
            await agi.set_variable("ROUTE_ACTION", "REJECT")
            return

        username = match.group(1)

        # 2. Look up SIP account and user
        row = session.execute(
            text("""
                SELECT s.id, s.user_id, s.username, s.caller_id_name, s.caller_id_number,
                       s.random_caller_id, s.max_channels, s.allow_p2p, s.allow_recording,
                       s.status as sip_status,
                       u.id as uid, u.name, u.role, u.parent_id, u.status as user_status,
                       u.billing_type, u.balance, u.credit_limit, u.rate_group_id,
                       u.min_balance_for_calls, u.daily_spend_limit, u.daily_call_limit,
                       u.destination_whitelist_enabled
                FROM sip_accounts s
                JOIN users u ON s.user_id = u.id
                WHERE s.username = :username
                LIMIT 1
            """),
            {"username": username},
        ).first()

        if not row:
            await agi.verbose(f"rSwitch: SIP account {username} not found")
            await agi.set_variable("ROUTE_ACTION", "REJECT")
            return

        if row.sip_status != "active" or row.user_status != "active":
            await agi.verbose(f"rSwitch: Account/user suspended")
            await agi.set_variable("ROUTE_ACTION", "REJECT")
            return

        # 3. Check destination whitelist/blacklist
        if row.destination_whitelist_enabled:
            allowed = session.execute(
                text("""
                    SELECT 1 FROM destination_list_entries e
                    JOIN destination_lists d ON e.destination_list_id = d.id
                    WHERE d.user_id = :user_id AND d.type = 'whitelist'
                    AND :dest LIKE CONCAT(e.prefix, '%')
                    LIMIT 1
                """),
                {"user_id": row.uid, "dest": extension},
            ).first()
            if not allowed:
                await agi.verbose("rSwitch: Destination not in whitelist")
                await agi.set_variable("ROUTE_ACTION", "REJECT")
                return

        # 4. Rate lookup and balance check
        if row.rate_group_id and row.billing_type == "prepaid":
            balance = Decimal(str(row.balance or 0))
            credit_limit = Decimal(str(row.credit_limit or 0))
            min_balance = Decimal(str(row.min_balance_for_calls or 0))
            available = balance + credit_limit

            if available < min_balance:
                await agi.verbose(f"rSwitch: Insufficient balance ({available})")
                await agi.set_variable("ROUTE_ACTION", "REJECT")
                await agi.set_variable("ROUTE_REJECT_REASON", "no_balance")
                return

        # 4a. Reseller blocked check (Redis flag — instant, no DB query)
        if row.parent_id:
            try:
                r = _get_redis()
                if r.exists(f"rswitch:reseller_blocked:{row.parent_id}"):
                    await agi.verbose(f"rSwitch: Reseller {row.parent_id} is blocked (insufficient balance)")
                    await agi.set_variable("ROUTE_ACTION", "REJECT")
                    await agi.set_variable("ROUTE_REJECT_REASON", "reseller_blocked")
                    return
            except Exception as e:
                logger.warning(f"rSwitch: Redis check failed for reseller block: {e}")

        # 4b. Reseller rate_group + balance check (for clients under resellers)
        if row.parent_id:
            reseller = session.execute(
                text("""
                    SELECT id, role, balance, credit_limit, billing_type, rate_group_id
                    FROM users WHERE id = :pid LIMIT 1
                """),
                {"pid": row.parent_id},
            ).first()

            # Only check if parent is a reseller (not super_admin)
            if reseller and reseller.role == 'reseller':
                # CHECK 1: Reseller MUST have a rate_group — billing is incomplete without it
                if not reseller.rate_group_id:
                    await agi.verbose(f"rSwitch: Reseller {reseller.id} has no rate_group — call blocked")
                    await agi.set_variable("ROUTE_ACTION", "REJECT")
                    await agi.set_variable("ROUTE_REJECT_REASON", "reseller_no_rate")
                    return

                # CHECK 2: Reseller must have sufficient balance (prepaid only)
                if reseller.billing_type == 'prepaid':
                    reseller_balance = Decimal(str(reseller.balance or 0))
                    reseller_credit = Decimal(str(reseller.credit_limit or 0))
                    reseller_available = reseller_balance + reseller_credit

                    if reseller_available <= Decimal("0"):
                        await agi.verbose(f"rSwitch: Reseller insufficient balance ({reseller_available})")
                        await agi.set_variable("ROUTE_ACTION", "REJECT")
                        await agi.set_variable("ROUTE_REJECT_REASON", "reseller_no_balance")
                        return

        # 4c. Credit control: calculate max call duration for prepaid users
        rate_per_minute = Decimal("0")
        if row.rate_group_id and row.billing_type == "prepaid":
            rate_row = session.execute(
                text("""
                    SELECT rate_per_minute FROM rates
                    WHERE rate_group_id = :rg_id
                    AND status = 'active'
                    AND effective_date <= CURDATE()
                    AND (end_date IS NULL OR end_date > CURDATE())
                    AND :dest LIKE CONCAT(prefix, '%')
                    ORDER BY LENGTH(prefix) DESC
                    LIMIT 1
                """),
                {"rg_id": row.rate_group_id, "dest": extension},
            ).first()

            if rate_row:
                rate_per_minute = Decimal(str(rate_row.rate_per_minute))
                if rate_per_minute > 0:
                    available_balance = Decimal(str(row.balance or 0)) + Decimal(str(row.credit_limit or 0))
                    rate_per_second = rate_per_minute / Decimal("60")
                    if rate_per_second > 0:
                        max_seconds = int(available_balance / rate_per_second)
                        # Cap at 4 hours max, minimum 60 seconds
                        max_seconds = max(60, min(max_seconds, 14400))
                        await agi.set_variable("RSWITCH_MAX_DURATION", str(max_seconds))
                        await agi.verbose(f"rSwitch: Credit control max_duration={max_seconds}s")

        # 5. Check daily limits
        if row.daily_call_limit or row.daily_spend_limit:
            today_start = datetime.now().replace(hour=0, minute=0, second=0)
            stats = session.execute(
                text("""
                    SELECT COUNT(*) as call_count, COALESCE(SUM(total_cost), 0) as total_spend
                    FROM call_records
                    WHERE user_id = :user_id AND call_start >= :today
                """),
                {"user_id": row.uid, "today": today_start},
            ).first()

            if row.daily_call_limit and stats.call_count >= row.daily_call_limit:
                await agi.verbose("rSwitch: Daily call limit reached")
                await agi.set_variable("ROUTE_ACTION", "REJECT")
                return

            if row.daily_spend_limit and Decimal(str(stats.total_spend)) >= Decimal(str(row.daily_spend_limit)):
                await agi.verbose("rSwitch: Daily spend limit reached")
                await agi.set_variable("ROUTE_ACTION", "REJECT")
                return

        # 6. Check for internal SIP-to-SIP call
        internal_target = session.execute(
            text("""
                SELECT s.id, s.username, s.user_id FROM sip_accounts s
                WHERE s.username = :ext AND s.status = 'active'
                LIMIT 1
            """),
            {"ext": extension},
        ).first()

        if internal_target:
            if not row.allow_p2p:
                await agi.verbose("rSwitch: P2P calls not allowed for this account")
                await agi.set_variable("ROUTE_ACTION", "REJECT")
                return

            # Check if callee is registered (has contact in ps_contacts)
            contact = session.execute(
                text("SELECT id FROM ps_contacts WHERE endpoint = :ext LIMIT 1"),
                {"ext": extension},
            ).first()

            # Create CDR regardless of registration status
            cdr_uuid = str(uuid.uuid4())

            if contact:
                # Callee is registered — normal P2P call
                session.execute(
                    text("""
                        INSERT INTO call_records
                        (uuid, sip_account_id, user_id, reseller_id, call_flow,
                         caller, callee, src_ip, user_agent,
                         call_start, disposition, status, created_at)
                        VALUES
                        (:uuid, :sip_id, :user_id, :reseller_id, 'sip_to_sip',
                         :caller, :callee, :src_ip, :user_agent,
                         NOW(), 'ANSWERED', 'in_progress', NOW())
                    """),
                    {
                        "uuid": cdr_uuid,
                        "sip_id": row.id,
                        "user_id": row.uid,
                        "reseller_id": row.parent_id,
                        "caller": caller_id,
                        "callee": extension,
                        "src_ip": src_ip,
                        "user_agent": user_agent,
                    },
                )
                session.commit()

                await agi.set_variable("ROUTE_ACTION", "DIAL_INTERNAL")
                await agi.set_variable("ROUTE_DIAL_STRING", f"PJSIP/{extension}")
                await agi.set_variable("ROUTE_DIAL_TIMEOUT", "60")
                await agi.set_variable("ROUTE_CLI_NAME", row.caller_id_name or username)
                await agi.set_variable("ROUTE_CLI_NUM", row.caller_id_number or caller_id)
                await agi.set_variable("CDR_UUID", cdr_uuid)
                await agi.set_variable("RECORD_CALL", "1" if row.allow_recording else "0")
                await agi.verbose(f"rSwitch: Internal call to {extension}")
            else:
                # Callee NOT registered — play wrong_number, CDR with FAILED
                session.execute(
                    text("""
                        INSERT INTO call_records
                        (uuid, sip_account_id, user_id, reseller_id, call_flow,
                         caller, callee, src_ip, user_agent,
                         call_start, call_end, duration, billsec,
                         disposition, hangup_cause, status, created_at)
                        VALUES
                        (:uuid, :sip_id, :user_id, :reseller_id, 'sip_to_sip',
                         :caller, :callee, :src_ip, :user_agent,
                         NOW(), NOW(), 0, 0,
                         'FAILED', 'CALLEE_NOT_REGISTERED', 'unbillable', NOW())
                    """),
                    {
                        "uuid": cdr_uuid,
                        "sip_id": row.id,
                        "user_id": row.uid,
                        "reseller_id": row.parent_id,
                        "caller": caller_id,
                        "callee": extension,
                        "src_ip": src_ip,
                        "user_agent": user_agent,
                    },
                )
                session.commit()

                await agi.set_variable("CDR_UUID", cdr_uuid)

                # Play announcement directly from AGI
                await agi.answer()
                await agi.exec("Wait", "0.5")
                await agi.exec("Playback", "IVR/wrong_number")
                await agi.set_variable("ROUTE_ACTION", "REJECT")
                await agi.verbose(f"rSwitch: {extension} not registered — played wrong_number")

            return

        # 7. Select trunk via route selection
        routes = session.execute(
            text("""
                SELECT tr.id, tr.prefix, tr.priority, tr.weight, tr.trunk_id,
                       tr.remove_prefix, tr.add_prefix, t.tech_prefix,
                       tr.mnp_enabled,
                       tr.time_start, tr.time_end, tr.days_of_week, tr.timezone,
                       t.name as trunk_name, t.id as tid, t.direction as trunk_direction,
                       t.cli_mode, t.cli_override_number, t.max_channels as trunk_max_channels
                FROM trunk_routes tr
                JOIN trunks t ON tr.trunk_id = t.id
                WHERE tr.status = 'active'
                AND t.status = 'active'
                AND t.direction IN ('outgoing', 'both')
                AND :dest LIKE CONCAT(tr.prefix, '%')
                ORDER BY LENGTH(tr.prefix) DESC, tr.priority ASC, tr.weight DESC
            """),
            {"dest": extension},
        ).fetchall()

        if not routes:
            # No trunk route — create CDR with FAILED and play wrong_number
            cdr_uuid = str(uuid.uuid4())
            session.execute(
                text("""
                    INSERT INTO call_records
                    (uuid, sip_account_id, user_id, reseller_id, call_flow,
                     caller, callee, destination, src_ip, user_agent,
                     call_start, call_end, duration, billsec,
                     disposition, hangup_cause, status, created_at)
                    VALUES
                    (:uuid, :sip_id, :user_id, :reseller_id, 'sip_to_trunk',
                     :caller, :callee, :callee, :src_ip, :user_agent,
                     NOW(), NOW(), 0, 0,
                     'FAILED', 'NO_ROUTE_FOUND', 'unbillable', NOW())
                """),
                {
                    "uuid": cdr_uuid,
                    "sip_id": row.id,
                    "user_id": row.uid,
                    "reseller_id": row.parent_id,
                    "caller": caller_id,
                    "callee": extension,
                    "src_ip": src_ip,
                    "user_agent": user_agent,
                },
            )
            session.commit()

            await agi.set_variable("CDR_UUID", cdr_uuid)

            # Play announcement directly from AGI
            await agi.answer()
            await agi.exec("Wait", "0.5")
            await agi.exec("Playback", "IVR/wrong_number")
            await agi.set_variable("ROUTE_ACTION", "REJECT")
            await agi.verbose(f"rSwitch: No route for {extension} — played wrong_number")
            return

        # Weighted random selection among same-priority routes
        primary = _weighted_select(routes)
        # Failover = next best route (different trunk)
        remaining = [r for r in routes if r.tid != primary.tid]

        # 8. Apply dial manipulation
        dial_number = extension

        # Remove prefix
        if primary.remove_prefix and dial_number.startswith(primary.remove_prefix):
            dial_number = dial_number[len(primary.remove_prefix):]

        # Add prefix
        if primary.add_prefix:
            dial_number = primary.add_prefix + dial_number

        # BD MNP auto-convert (after remove/add prefix)
        if primary.mnp_enabled:
            dial_number = _apply_bd_mnp(dial_number)

        # Tech prefix
        if primary.tech_prefix:
            dial_number = primary.tech_prefix + dial_number

        # 9. Build dial string
        trunk_endpoint = f"trunk-{primary.trunk_direction}-{primary.tid}"
        dial_string = f"PJSIP/{dial_number}@{trunk_endpoint}"

        # Failover trunk (next best from remaining, excluding primary trunk)
        failover_string = ""
        if remaining:
            failover = remaining[0]
            fo_number = extension
            if failover.remove_prefix and fo_number.startswith(failover.remove_prefix):
                fo_number = fo_number[len(failover.remove_prefix):]
            if failover.add_prefix:
                fo_number = failover.add_prefix + fo_number
            if failover.mnp_enabled:
                fo_number = _apply_bd_mnp(fo_number)
            if failover.tech_prefix:
                fo_number = failover.tech_prefix + fo_number
            fo_endpoint = f"trunk-{failover.trunk_direction}-{failover.tid}"
            failover_string = f"PJSIP/{fo_number}@{fo_endpoint}"

        # 10. Determine CLI
        cli_name = row.caller_id_name or username
        cli_num = row.caller_id_number or caller_id

        if primary.cli_mode == "override" and primary.cli_override_number:
            cli_num = primary.cli_override_number
        elif primary.cli_mode == "hide":
            cli_num = extension

        # 11. Create CDR
        cdr_uuid = str(uuid.uuid4())
        session.execute(
            text("""
                INSERT INTO call_records
                (uuid, sip_account_id, user_id, reseller_id, call_flow,
                 caller, callee, destination, outgoing_trunk_id,
                 src_ip, user_agent,
                 call_start, disposition, status, created_at)
                VALUES
                (:uuid, :sip_id, :user_id, :reseller_id, 'sip_to_trunk',
                 :caller, :callee, :destination, :trunk_id,
                 :src_ip, :user_agent,
                 NOW(), 'ANSWERED', 'in_progress', NOW())
            """),
            {
                "uuid": cdr_uuid,
                "sip_id": row.id,
                "user_id": row.uid,
                "reseller_id": row.parent_id,
                "caller": caller_id,
                "callee": extension,
                "destination": dial_number,
                "trunk_id": primary.tid,
                "src_ip": src_ip,
                "user_agent": user_agent,
            },
        )
        session.commit()

        # 12. Set channel variables for dialplan
        await agi.set_variable("ROUTE_ACTION", "DIAL")
        await agi.set_variable("ROUTE_DIAL_STRING", dial_string)
        await agi.set_variable("ROUTE_FAILOVER", failover_string)
        await agi.set_variable("ROUTE_DIAL_TIMEOUT", "90")
        await agi.set_variable("ROUTE_CLI_NAME", cli_name)
        await agi.set_variable("ROUTE_CLI_NUM", cli_num)
        await agi.set_variable("CDR_UUID", cdr_uuid)
        await agi.set_variable("RECORD_CALL", "1" if row.allow_recording else "0")

        # Set absolute timeout for prepaid credit control
        max_dur = await agi.get_variable("RSWITCH_MAX_DURATION")
        if max_dur:
            await agi.set_variable("CHANNEL(ABSOLUTE_TIMEOUT)", max_dur)
            await agi.verbose(f"rSwitch: ABSOLUTE_TIMEOUT set to {max_dur}s")

        # Store call metadata in Redis for credit control safety-net
        try:
            r = _get_redis()
            call_key = f"rswitch:active_call:{cdr_uuid}"
            r.setex(call_key, 14400, json.dumps({
                "user_id": int(row.uid),
                "reseller_id": int(row.parent_id) if row.parent_id else None,
                "billing_type": row.billing_type,
                "rate_per_minute": float(rate_per_minute),
                "channel": channel,
                "start_time": time.time(),
            }))
        except Exception as e:
            logger.warning(f"rSwitch: Failed to store credit control metadata: {e}")

        await agi.verbose(
            f"rSwitch: Route {extension} via {primary.trunk_name} "
            f"({dial_string}), failover={failover_string or 'none'}"
        )

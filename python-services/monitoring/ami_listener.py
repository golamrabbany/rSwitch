"""
Asterisk AMI event listener with WebSocket broadcasting.

Tracks active channels in real-time and pushes events to connected WebSocket clients.
Also triggers billing tasks when calls end.
"""

import asyncio
import logging
import time
from datetime import datetime
from typing import Optional

from panoramisk import Manager

from shared.config import get_settings

logger = logging.getLogger(__name__)


class ActiveCall:
    """Represents an active call tracked via AMI events."""

    __slots__ = [
        "unique_id", "linked_id", "channel", "caller", "callee", "call_flow",
        "state", "started_at", "answered_at", "trunk", "sip_account", "client",
    ]

    def __init__(self, unique_id: str, channel: str, linked_id: str = ""):
        self.unique_id = unique_id
        self.linked_id = linked_id or unique_id  # Linkedid groups legs of one call
        self.channel = channel
        self.caller = ""
        self.callee = ""
        self.call_flow = ""  # inbound / outbound
        self.state = "ringing"  # ringing, answered, processing
        self.started_at = time.time()
        self.answered_at: Optional[float] = None
        self.trunk = ""
        self.sip_account = ""
        self.client = ""  # User name owning the SIP account

    def to_dict(self) -> dict:
        # Duration only counts billable time (post-answer). Ringing time = 0.
        duration = int(time.time() - self.answered_at) if self.answered_at else 0

        return {
            "unique_id": self.unique_id,
            "linked_id": self.linked_id,
            "channel": self.channel,
            "caller": self.caller,
            "callee": self.callee,
            "call_flow": self.call_flow,
            "state": self.state,
            "duration": duration,
            "started_at": self.started_at,
            "answered_at": self.answered_at,
            "trunk": self.trunk,
            "sip_account": self.sip_account,
            "client": self.client,
        }


class AMIListener:
    """
    Connects to Asterisk AMI, tracks active channels, and broadcasts
    events to WebSocket clients for real-time monitoring.
    """

    def __init__(self):
        self.settings = get_settings()
        self.manager: Optional[Manager] = None
        self._active_calls: dict[str, ActiveCall] = {}  # unique_id → ActiveCall
        # linked_id → unique_id of the leg that owns the UI row. Every
        # call_start/answered/end broadcast for a given linked_id is keyed
        # to this uid so the JS only ever creates/updates one row per call.
        self._displayed_uid: dict[str, str] = {}
        self._registered_contacts: dict[str, dict] = {}  # username → {ip, port, user_agent, registered_at}
        self._sip_to_client: dict[str, str] = {}  # sip username → owning user's name
        self._sip_client_loaded_at: float = 0.0
        self._trunk_status: dict[str, str] = {}  # endpoint name → 'Avail' | 'Unreachable' | 'Unknown'
        self._trunk_status_started_at: float = time.time()
        self._ws_clients: set = set()  # Connected WebSocket clients
        self._connected = False
        self._reconnect_task: Optional[asyncio.Task] = None

    # ─────────────────────────────────────────────────────
    # Connection management
    # ─────────────────────────────────────────────────────

    async def connect(self):
        """Connect to Asterisk AMI with auto-reconnect."""
        self.manager = Manager(
            host=self.settings.asterisk_ami_host,
            port=self.settings.asterisk_ami_port,
            username=self.settings.asterisk_ami_user,
            secret=self.settings.asterisk_ami_secret,
            ping_delay=10,
        )

        # Register event handlers
        self.manager.register_event("Newchannel", self._on_new_channel)
        self.manager.register_event("Newstate", self._on_new_state)
        self.manager.register_event("Bridge", self._on_bridge)
        self.manager.register_event("BridgeEnter", self._on_bridge)
        self.manager.register_event("Hangup", self._on_hangup)
        self.manager.register_event("Cdr", self._on_cdr)
        self.manager.register_event("ContactStatus", self._on_contact_status)

        try:
            await self.manager.connect()
            self._connected = True
            logger.info(
                f"AMI connected to {self.settings.asterisk_ami_host}:"
                f"{self.settings.asterisk_ami_port}"
            )
            # Load current state on connect
            self._reload_sip_client_map()
            await self._load_active_channels()
            await self._load_registered_contacts()
            await self._load_trunk_status()
            # Start periodic contact refresh
            asyncio.create_task(self._periodic_contact_refresh())
            # Periodic snapshot so any client desync (missed Hangup, dropped
            # WS frame) self-corrects within a few seconds.
            asyncio.create_task(self._periodic_snapshot())
        except Exception as e:
            self._connected = False
            logger.warning(f"AMI connection failed: {e}")
            # Start reconnect loop
            self._reconnect_task = asyncio.create_task(self._reconnect_loop())

    async def _reconnect_loop(self):
        """Attempt to reconnect to AMI every 10 seconds."""
        while not self._connected:
            await asyncio.sleep(10)
            try:
                if self.manager:
                    await self.manager.connect()
                    self._connected = True
                    logger.info("AMI reconnected")
                    await self._load_active_channels()
                    await self._load_registered_contacts()
            except Exception as e:
                logger.debug(f"AMI reconnect attempt failed: {e}")

    async def disconnect(self):
        """Disconnect from Asterisk AMI."""
        self._connected = False
        if self._reconnect_task:
            self._reconnect_task.cancel()
        if self.manager:
            self.manager.close()
            logger.info("AMI disconnected")

    async def _load_active_channels(self):
        """Load current active channels from Asterisk on startup."""
        try:
            response = await self.manager.send_action({
                "Action": "CoreShowChannels",
            })
            # Parse response events for active channels
            for event in response:
                uid = getattr(event, 'Uniqueid', '') or ''
                channel = getattr(event, 'Channel', '') or ''
                # Skip empty/metadata entries
                if not uid or not channel or uid == '0' or channel == '0':
                    continue
                if uid not in self._active_calls:
                    linked_id = getattr(event, 'Linkedid', '') or uid
                    call = ActiveCall(uid, channel, linked_id)
                    call.caller = getattr(event, 'CallerIDNum', '') or ''
                    call.callee = getattr(event, 'ConnectedLineNum', '') or getattr(event, 'Exten', '') or ''
                    if call.state == "ringing" and getattr(event, 'Duration', '0') != '0':
                        call.state = "answered"
                        call.answered_at = time.time()
                    # Direction + sip account + client (mirror _on_new_channel logic)
                    if "trunk" in channel.lower() or channel.startswith("PJSIP/trunk"):
                        call.call_flow = "inbound"
                        call.trunk = channel.split("/")[1].split("-")[0] if "/" in channel else ""
                    elif "/" in channel:
                        call.call_flow = "outbound"
                        call.sip_account = channel.split("/")[1].split("-")[0]
                        call.client = self._lookup_client(call.sip_account)
                    # Only add if it looks like a real channel
                    if call.caller or call.callee or 'PJSIP' in channel:
                        self._active_calls[uid] = call
                        # First leg per linked_id owns the UI row.
                        self._displayed_uid.setdefault(call.linked_id, uid)

            count = len(self._active_calls)
            if count > 0:
                logger.info(f"Loaded {count} active channels from Asterisk")
                await self._broadcast({
                    "type": "snapshot",
                    "calls": self.get_active_calls_list(),
                    "stats": self.get_stats(),
                })
        except Exception as e:
            logger.debug(f"Could not load active channels: {e}")

    # ─────────────────────────────────────────────────────
    # AMI Event Handlers
    # ─────────────────────────────────────────────────────

    def _reload_sip_client_map(self) -> None:
        """Cache SIP username → owning user's name. Called on connect and
        refreshed periodically; ~14k rows is well under 2 MB in memory."""
        try:
            from shared.database import get_sync_engine
            from sqlalchemy import text
            engine = get_sync_engine()
            with engine.connect() as conn:
                rows = conn.execute(text(
                    "SELECT s.username, u.name FROM sip_accounts s "
                    "JOIN users u ON s.user_id = u.id"
                )).fetchall()
            self._sip_to_client = {r.username: (r.name or "") for r in rows}
            self._sip_client_loaded_at = time.time()
            logger.info(f"Loaded {len(self._sip_to_client)} SIP→client mappings")
        except Exception as e:
            logger.warning(f"Could not load SIP→client map: {e}")

    def _lookup_client(self, sip_username: str) -> str:
        """Resolve client name. If the cache misses (new account since startup),
        do a one-shot DB read so it appears immediately."""
        if not sip_username:
            return ""
        if sip_username in self._sip_to_client:
            return self._sip_to_client[sip_username]
        try:
            from shared.database import get_sync_engine
            from sqlalchemy import text
            engine = get_sync_engine()
            with engine.connect() as conn:
                row = conn.execute(text(
                    "SELECT u.name FROM sip_accounts s "
                    "JOIN users u ON s.user_id = u.id WHERE s.username = :u LIMIT 1"
                ), {"u": sip_username}).first()
            name = (row.name if row else "") or ""
            self._sip_to_client[sip_username] = name
            return name
        except Exception:
            return ""

    async def _on_new_channel(self, manager, event):
        """New channel created — a call is starting."""
        uid = event.get("Uniqueid", "")
        channel = event.get("Channel", "")
        linked_id = event.get("Linkedid", "") or uid

        if not uid or not channel:
            return

        call = ActiveCall(uid, channel, linked_id)
        call.caller = event.get("CallerIDNum", "")
        call.callee = event.get("Exten", "")
        call.state = "ringing"

        # Detect direction from channel name
        if "trunk" in channel.lower() or channel.startswith("PJSIP/trunk"):
            call.call_flow = "inbound"
            call.trunk = channel.split("/")[1].split("-")[0] if "/" in channel else ""
        else:
            call.call_flow = "outbound"
            call.sip_account = channel.split("/")[1].split("-")[0] if "/" in channel else ""
            call.client = self._lookup_client(call.sip_account)

        # Asterisk creates 2 channels per call (caller leg + dialed leg)
        # sharing one Linkedid. The first leg owns the UI row; subsequent
        # legs feed updates into it without spawning a second row.
        is_secondary_leg = linked_id in self._displayed_uid
        self._active_calls[uid] = call

        if not is_secondary_leg:
            self._displayed_uid[linked_id] = uid
            await self._broadcast({
                "type": "call_start",
                "call": call.to_dict(),
                "stats": self.get_stats(),
            })

        logger.debug(
            f"New channel: uid={uid}, linked={linked_id}, "
            f"secondary={is_secondary_leg}, caller={call.caller}, callee={call.callee}"
        )

    def _displayed_leg(self, linked_id: str) -> Optional[ActiveCall]:
        """Return the leg whose unique_id is broadcast as the UI row for
        this linked_id, or None if no row has been broadcast yet."""
        uid = self._displayed_uid.get(linked_id)
        return self._active_calls.get(uid) if uid else None

    @staticmethod
    def _is_meaningful(value: str) -> bool:
        return bool(value) and value not in ("<unknown>", "s")

    def _mirror_to_displayed(self, leg: ActiveCall) -> ActiveCall:
        """Mirror state + any richer caller/callee data from this leg onto
        the displayed leg for its linked_id, so subsequent broadcasts of
        the displayed leg reflect the latest call state. Returns the leg
        whose to_dict() should be broadcast (displayed if known, else self)."""
        displayed = self._displayed_leg(leg.linked_id)
        if displayed is None or displayed is leg:
            return leg
        if leg.state == "answered" and displayed.state != "answered":
            displayed.state = "answered"
            displayed.answered_at = leg.answered_at
        if self._is_meaningful(leg.caller) and not self._is_meaningful(displayed.caller):
            displayed.caller = leg.caller
        if self._is_meaningful(leg.callee) and not self._is_meaningful(displayed.callee):
            displayed.callee = leg.callee
        return displayed

    async def _on_new_state(self, manager, event):
        """Channel state changed — typically ringing → answered."""
        uid = event.get("Uniqueid", "")
        state = event.get("ChannelStateDesc", "").lower()

        if uid in self._active_calls:
            call = self._active_calls[uid]
            if state == "up" and call.state != "answered":
                call.state = "answered"
                call.answered_at = time.time()

                # Update callee from ConnectedLineNum if available
                connected = event.get("ConnectedLineNum", "")
                if connected and connected != "<unknown>":
                    call.callee = connected

                broadcast_leg = self._mirror_to_displayed(call)
                await self._broadcast({
                    "type": "call_answered",
                    "call": broadcast_leg.to_dict(),
                    "stats": self.get_stats(),
                })
            elif state == "ringing":
                call.state = "ringing"

    async def _on_bridge(self, manager, event):
        """Two channels bridged — call is connected."""
        uid = event.get("Uniqueid", "") or event.get("Uniqueid1", "")

        if uid in self._active_calls:
            call = self._active_calls[uid]
            if call.state != "answered":
                call.state = "answered"
                call.answered_at = time.time()

                broadcast_leg = self._mirror_to_displayed(call)
                await self._broadcast({
                    "type": "call_answered",
                    "call": broadcast_leg.to_dict(),
                    "stats": self.get_stats(),
                })

    async def _on_hangup(self, manager, event):
        """Channel hung up — call ended."""
        uid = event.get("Uniqueid", "")

        call = self._active_calls.pop(uid, None)
        if not call:
            return

        displayed_uid = self._displayed_uid.get(call.linked_id)

        # Only emit call_end when the LAST leg of this linked call is gone.
        # The UI key is the leg that was broadcast as call_start (the first leg
        # for this linked_id). If other legs remain, this hangup is silent.
        other_legs_remain = any(
            other.linked_id == call.linked_id
            for other in self._active_calls.values()
        )

        if other_legs_remain:
            # If the leg that owned the UI row dropped first, hand the row
            # over to a remaining sibling so future state changes still
            # update it and the eventual call_end can clear it.
            if displayed_uid == uid:
                sibling = next(
                    (c for c in self._active_calls.values() if c.linked_id == call.linked_id),
                    None,
                )
                if sibling:
                    self._displayed_uid[call.linked_id] = sibling.unique_id
                    self._mirror_to_displayed(call)
        else:
            self._displayed_uid.pop(call.linked_id, None)
            await self._broadcast({
                "type": "call_end",
                "unique_id": displayed_uid or uid,
                "linked_id": call.linked_id,
                "caller": call.caller,
                "callee": call.callee,
                "duration": int(time.time() - call.started_at),
                "stats": self.get_stats(),
            })

        logger.debug(
            f"Hangup: uid={uid}, linked={call.linked_id}, "
            f"others_remain={other_legs_remain}"
        )

    async def _on_cdr(self, manager, event):
        """CDR written — trigger billing."""
        uid = event.get("UniqueID", "")
        disposition = event.get("Disposition", "")
        billsec = int(event.get("BillableSeconds", 0) or event.get("Duration", 0))

        if disposition == "ANSWERED" and billsec > 0:
            try:
                from shared.database import get_sync_engine
                from sqlalchemy import text

                engine = get_sync_engine()
                with engine.connect() as conn:
                    result = conn.execute(
                        text(
                            "SELECT id FROM call_records "
                            "WHERE uuid = :uuid AND status = 'in_progress' "
                            "LIMIT 1"
                        ),
                        {"uuid": uid},
                    )
                    row = result.fetchone()

                if row:
                    from billing.tasks import rate_and_charge
                    rate_and_charge.delay(row[0])
                    logger.info(f"Queued billing for CDR {row[0]} (uuid={uid})")
            except Exception as e:
                logger.error(f"Error processing CDR event: {e}")

    # ─────────────────────────────────────────────────────
    # SIP Registration tracking
    # ─────────────────────────────────────────────────────

    async def _on_contact_status(self, manager, event):
        """Track SIP registration AND trunk-qualify status changes in real-time."""
        uri = event.get("URI", "")
        status = event.get("ContactStatus", "")
        aor = event.get("AOR", "")

        if not aor:
            return

        import re

        # Trunk AORs are named "trunk-{direction}-{tid}-aor". The endpoint is
        # the same string with the "-aor" suffix removed.
        trunk_match = re.match(r"^(trunk-(?:in|out|both)-\d+)-aor$", aor)
        if trunk_match:
            endpoint = trunk_match.group(1)
            normalized = "Avail" if status in ("Created", "Updated", "Reachable") else "Unreachable"
            prev = self._trunk_status.get(endpoint)
            self._trunk_status[endpoint] = normalized
            if prev != normalized:
                logger.info(f"Trunk {endpoint} status: {prev or 'unknown'} → {normalized}")
            return

        # Extract IP from URI: sip:username@IP:port
        ip_match = re.search(r"@([\d.]+)", uri)
        ip = ip_match.group(1) if ip_match else ""

        if status in ("Created", "Updated", "Reachable"):
            self._registered_contacts[aor] = {
                "ip": ip,
                "uri": uri,
                "user_agent": event.get("UserAgent", ""),
                "registered_at": time.time(),
                "status": "Avail",
            }
            logger.info(f"SIP registered: {aor} from {ip}")

            # Broadcast to WebSocket clients
            await self._broadcast({
                "type": "sip_registered",
                "username": aor,
                "ip": ip,
                "user_agent": event.get("UserAgent", ""),
            })

            # Update DB: last_registered_at
            try:
                from shared.database import get_sync_engine
                from sqlalchemy import text
                engine = get_sync_engine()
                with engine.connect() as conn:
                    conn.execute(
                        text(
                            "UPDATE sip_accounts SET last_registered_at = NOW(), "
                            "last_registered_ip = :ip WHERE username = :username"
                        ),
                        {"ip": ip, "username": aor},
                    )
                    conn.commit()
            except Exception as e:
                logger.debug(f"Could not update registration in DB: {e}")

        elif status in ("Removed", "Unreachable"):
            self._registered_contacts.pop(aor, None)
            logger.info(f"SIP unregistered: {aor}")

            # Broadcast to WebSocket clients
            await self._broadcast({
                "type": "sip_unregistered",
                "username": aor,
            })

    async def _load_registered_contacts(self):
        """Sync registered contacts from ps_contacts table. Detects new/removed."""
        try:
            import re
            from shared.database import get_sync_engine
            from sqlalchemy import text

            engine = get_sync_engine()
            with engine.connect() as conn:
                rows = conn.execute(
                    text("SELECT id, uri, user_agent, endpoint FROM ps_contacts WHERE uri IS NOT NULL")
                ).fetchall()

            current_db = {}
            for row in rows:
                aor = row.endpoint or row.id.split("^3B")[0].split(";")[0]
                uri = (row.uri or "").replace("^3B", ";")
                ip_match = re.search(r"@([\d.]+)", uri)
                ip = ip_match.group(1) if ip_match else ""

                # Skip trunk contacts
                if aor and uri and not aor.startswith("trunk-"):
                    current_db[aor] = {
                        "ip": ip,
                        "uri": uri,
                        "user_agent": row.user_agent or "",
                        "registered_at": time.time(),
                        "status": "Avail",
                    }

            # Detect newly registered
            for aor, info in current_db.items():
                if aor not in self._registered_contacts:
                    logger.info(f"SIP registered (DB sync): {aor} @ {info['ip']}")
                    await self._broadcast({
                        "type": "sip_registered",
                        "username": aor,
                        "ip": info["ip"],
                    })
                self._registered_contacts[aor] = info

            # Detect unregistered (was in cache but no longer in DB)
            stale = [aor for aor in self._registered_contacts if aor not in current_db and not aor.startswith("trunk-")]
            for aor in stale:
                logger.info(f"SIP unregistered (DB sync): {aor}")
                self._registered_contacts.pop(aor, None)
                await self._broadcast({
                    "type": "sip_unregistered",
                    "username": aor,
                })

            count = len(self._registered_contacts)
            if count > 0:
                logger.debug(f"Contact sync: {count} registered")
        except Exception as e:
            logger.debug(f"Could not load contacts: {e}")

    async def _periodic_contact_refresh(self):
        """Refresh contacts from ps_contacts every 1 second for instant updates."""
        while True:
            await asyncio.sleep(1)
            try:
                await self._load_registered_contacts()
            except Exception as e:
                logger.debug(f"Periodic contact refresh failed: {e}")

    async def _periodic_snapshot(self):
        """Broadcast a full active-calls snapshot every 10s so any UI desync
        (missed event, dropped WS frame) self-corrects shortly after."""
        while True:
            await asyncio.sleep(10)
            if not self._ws_clients:
                continue
            try:
                await self._broadcast({
                    "type": "snapshot",
                    "calls": self.get_active_calls_list(),
                    "stats": self.get_stats(),
                })
            except Exception as e:
                logger.debug(f"Periodic snapshot failed: {e}")

    def get_registered_contacts(self) -> dict:
        """Return all registered contacts as {username: {ip, status, ...}}."""
        return dict(self._registered_contacts)

    # ─────────────────────────────────────────────────────
    # WebSocket client management
    # ─────────────────────────────────────────────────────

    def register_client(self, websocket):
        """Register a new WebSocket client."""
        self._ws_clients.add(websocket)
        logger.info(f"WebSocket client connected ({len(self._ws_clients)} total)")

    def unregister_client(self, websocket):
        """Remove a disconnected WebSocket client."""
        self._ws_clients.discard(websocket)
        logger.info(f"WebSocket client disconnected ({len(self._ws_clients)} total)")

    async def _broadcast(self, message: dict):
        """Send a message to all connected WebSocket clients."""
        if not self._ws_clients:
            return

        dead_clients = set()
        for ws in self._ws_clients:
            try:
                await ws.send_json(message)
            except Exception:
                dead_clients.add(ws)

        # Clean up dead connections
        for ws in dead_clients:
            self._ws_clients.discard(ws)

    # ─────────────────────────────────────────────────────
    # Data access
    # ─────────────────────────────────────────────────────

    def _dedupe_legs(self) -> list[ActiveCall]:
        """One Asterisk call has 2 legs (caller + dialed). Return the leg
        that owns the UI row per linked_id (tracked via _displayed_uid),
        so snapshots key rows the same way live events do."""
        result: list[ActiveCall] = []
        seen_links: set[str] = set()

        for linked_id, displayed_uid in self._displayed_uid.items():
            leg = self._active_calls.get(displayed_uid)
            if leg is not None and linked_id not in seen_links:
                result.append(leg)
                seen_links.add(linked_id)

        # Fallback for any leg lacking a displayed mapping (shouldn't happen
        # in steady state but keeps recovery from partial loads safe).
        for call in self._active_calls.values():
            if call.linked_id not in seen_links:
                seen_links.add(call.linked_id)
                result.append(call)

        return result

    def get_active_calls_list(self) -> list[dict]:
        """Return active calls as dicts, deduped by linked_id (one per call)."""
        return [c.to_dict() for c in self._dedupe_legs()]

    def get_stats(self) -> dict:
        """Return summary statistics for active calls (deduped by linked_id)."""
        calls = self._dedupe_legs()
        total = len(calls)
        answered = sum(1 for c in calls if c.state == "answered")
        ringing = sum(1 for c in calls if c.state == "ringing")
        inbound = sum(1 for c in calls if c.call_flow == "inbound")
        outbound = sum(1 for c in calls if c.call_flow == "outbound")

        return {
            "total": total,
            "answered": answered,
            "ringing": ringing,
            "inbound": inbound,
            "outbound": outbound,
        }

    @property
    def active_count(self) -> int:
        return len(self._dedupe_legs())

    async def mark_call_rejected(self, unique_id: str, reason: str = "rejected") -> None:
        """Called by the AGI handler when it rejects a call (e.g.
        trunk_unreachable, no_balance). The dialplan still does Answer()
        + Playback() to play a prompt, which would otherwise show the
        channel as 'Answered' in Active Calls. Drop it from the live
        list right away and tell WS clients to remove the row; the
        normal Hangup event will be a no-op for it."""
        call = self._active_calls.pop(unique_id, None)
        if not call:
            return
        displayed_uid = self._displayed_uid.pop(call.linked_id, None) or unique_id
        await self._broadcast({
            "type": "call_end",
            "unique_id": displayed_uid,
            "linked_id": call.linked_id,
            "caller": call.caller,
            "callee": call.callee,
            "duration": 0,
            "reason": reason,
            "stats": self.get_stats(),
        })
        logger.debug(f"Call {unique_id} marked rejected ({reason})")

    # ─────────────────────────────────────────────────────
    # Trunk reachability
    # ─────────────────────────────────────────────────────

    async def _load_trunk_status(self) -> None:
        """Bootstrap trunk reachability from Asterisk on startup. Without this
        we'd have no data until the next qualify run (~60s).

        Uses `asterisk -rx "pjsip show contacts"` because the AMI
        PJSIPShowContacts action's event format isn't reliable across
        Asterisk versions for trunk-style AORs. Output line example:
            Contact:  trunk-both-3-aor/sip:10.243.16.5:5060   <hash> Avail   55.852
        """
        import asyncio as _asyncio
        import re
        try:
            proc = await _asyncio.create_subprocess_exec(
                "asterisk", "-rx", "pjsip show contacts",
                stdout=_asyncio.subprocess.PIPE,
                stderr=_asyncio.subprocess.DEVNULL,
            )
            stdout, _ = await proc.communicate()
            text_out = stdout.decode("utf-8", errors="replace")

            pattern = re.compile(
                r"Contact:\s+(trunk-(?:in|out|both)-\d+)-aor/\S+\s+\S+\s+(\S+)"
            )
            for endpoint, status in pattern.findall(text_out):
                # Status field is one of: Avail, NonQual, Unknown, Unreachable, NotInUse
                self._trunk_status[endpoint] = "Avail" if status == "Avail" else "Unreachable"
            logger.info(f"Loaded trunk status: {self._trunk_status}")
        except Exception as e:
            logger.debug(f"Could not load trunk status: {e}")

    def is_trunk_available(self, endpoint: str) -> bool:
        """Return True if the trunk's contact is qualified Reachable.
        During a 90 s grace window after engine start, treat unknown as
        available so we don't reject calls before the first qualify run."""
        status = self._trunk_status.get(endpoint)
        if status == "Avail":
            return True
        if status == "Unreachable":
            return False
        # Unknown: be permissive briefly, then start failing closed.
        if time.time() - self._trunk_status_started_at < 90:
            return True
        return False

    def get_trunk_status_map(self) -> dict[str, str]:
        return dict(self._trunk_status)

    @property
    def is_connected(self) -> bool:
        return self._connected


# Singleton
_ami_listener: Optional[AMIListener] = None


def get_ami_listener() -> AMIListener:
    global _ami_listener
    if _ami_listener is None:
        _ami_listener = AMIListener()
    return _ami_listener

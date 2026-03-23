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
        "unique_id", "channel", "caller", "callee", "call_flow",
        "state", "started_at", "answered_at", "trunk", "sip_account",
    ]

    def __init__(self, unique_id: str, channel: str):
        self.unique_id = unique_id
        self.channel = channel
        self.caller = ""
        self.callee = ""
        self.call_flow = ""  # inbound / outbound
        self.state = "ringing"  # ringing, answered, processing
        self.started_at = time.time()
        self.answered_at: Optional[float] = None
        self.trunk = ""
        self.sip_account = ""

    def to_dict(self) -> dict:
        duration = 0
        if self.answered_at:
            duration = int(time.time() - self.answered_at)
        elif self.started_at:
            duration = int(time.time() - self.started_at)

        return {
            "unique_id": self.unique_id,
            "channel": self.channel,
            "caller": self.caller,
            "callee": self.callee,
            "call_flow": self.call_flow,
            "state": self.state,
            "duration": duration,
            "started_at": self.started_at,
            "trunk": self.trunk,
            "sip_account": self.sip_account,
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

        try:
            await self.manager.connect()
            self._connected = True
            logger.info(
                f"AMI connected to {self.settings.asterisk_ami_host}:"
                f"{self.settings.asterisk_ami_port}"
            )
            # Load current active channels on connect
            await self._load_active_channels()
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
                if hasattr(event, 'Uniqueid') and hasattr(event, 'Channel'):
                    uid = event.Uniqueid
                    if uid not in self._active_calls:
                        call = ActiveCall(uid, event.Channel)
                        call.caller = getattr(event, 'CallerIDNum', '')
                        call.callee = getattr(event, 'ConnectedLineNum', '') or getattr(event, 'Exten', '')
                        call.state = "answered" if getattr(event, 'Duration', '0') != '0' else "ringing"
                        self._active_calls[uid] = call

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

    async def _on_new_channel(self, manager, event):
        """New channel created — a call is starting."""
        uid = event.get("Uniqueid", "")
        channel = event.get("Channel", "")

        if not uid or not channel:
            return

        call = ActiveCall(uid, channel)
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

        self._active_calls[uid] = call

        await self._broadcast({
            "type": "call_start",
            "call": call.to_dict(),
            "stats": self.get_stats(),
        })

        logger.debug(f"New channel: uid={uid}, caller={call.caller}, callee={call.callee}")

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

                await self._broadcast({
                    "type": "call_answered",
                    "call": call.to_dict(),
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

                await self._broadcast({
                    "type": "call_answered",
                    "call": call.to_dict(),
                    "stats": self.get_stats(),
                })

    async def _on_hangup(self, manager, event):
        """Channel hung up — call ended."""
        uid = event.get("Uniqueid", "")

        call = self._active_calls.pop(uid, None)
        if call:
            await self._broadcast({
                "type": "call_end",
                "unique_id": uid,
                "caller": call.caller,
                "callee": call.callee,
                "duration": int(time.time() - call.started_at),
                "stats": self.get_stats(),
            })
            logger.debug(f"Hangup: uid={uid}, caller={call.caller}")

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

    def get_active_calls_list(self) -> list[dict]:
        """Return all active calls as a list of dicts."""
        return [call.to_dict() for call in self._active_calls.values()]

    def get_stats(self) -> dict:
        """Return summary statistics for active calls."""
        total = len(self._active_calls)
        answered = sum(1 for c in self._active_calls.values() if c.state == "answered")
        ringing = sum(1 for c in self._active_calls.values() if c.state == "ringing")
        inbound = sum(1 for c in self._active_calls.values() if c.call_flow == "inbound")
        outbound = sum(1 for c in self._active_calls.values() if c.call_flow == "outbound")

        return {
            "total": total,
            "answered": answered,
            "ringing": ringing,
            "inbound": inbound,
            "outbound": outbound,
        }

    @property
    def active_count(self) -> int:
        return len(self._active_calls)

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

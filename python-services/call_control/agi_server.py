"""
FastAGI Server — Port of app/Console/Commands/AgiServer.php

Async TCP server that handles AGI requests from Asterisk.
Routes to appropriate handler based on script name in AGI request URL.

Scripts:
- route_outbound → OutboundCallHandler
- route_inbound  → InboundCallHandler
- call_end       → CallEndHandler
- broadcast_call → BroadcastCallHandler
- forward_call   → ForwardCallHandler
"""

import asyncio
import logging

from call_control.agi_protocol import AgiConnection
from call_control.outbound_handler import OutboundCallHandler
from call_control.inbound_handler import InboundCallHandler
from call_control.call_end_handler import CallEndHandler
from call_control.broadcast_handler import BroadcastCallHandler
from call_control.forward_handler import ForwardCallHandler

logger = logging.getLogger(__name__)

# Handler instances
_outbound = OutboundCallHandler()
_inbound = InboundCallHandler()
_call_end = CallEndHandler()
_broadcast = BroadcastCallHandler()
_forward = ForwardCallHandler()


async def handle_connection(reader: asyncio.StreamReader, writer: asyncio.StreamWriter):
    """Handle a single AGI connection from Asterisk.

    No DB session is opened here — handlers offload sync DB work via
    shared.database.db_thread(), which checks out a fresh session per
    transactional unit. This keeps the asyncio event loop responsive
    even under sustained 50-70 cps load.
    """
    peer = writer.get_extra_info("peername")
    conn = AgiConnection(reader, writer)

    try:
        await conn.parse()

        script = conn.get_script()
        logger.info(f"AGI request: script={script}, channel={conn.get_channel()}")

        if script == "route_outbound":
            await _outbound.handle(conn)
        elif script == "route_inbound":
            await _inbound.handle(conn)
        elif script == "call_end":
            await _call_end.handle(conn)
        elif script == "broadcast_call":
            await _broadcast.handle(conn)
        elif script == "forward_call":
            await _forward.handle(conn)
        else:
            logger.warning(f"Unknown AGI script: {script}")
            await conn.verbose(f"rSwitch: Unknown script '{script}'")

    except Exception as e:
        logger.error(f"AGI connection error from {peer}: {e}", exc_info=True)
    finally:
        conn.close()


async def start_agi_server(host: str = "0.0.0.0", port: int = 4573):
    """Start the FastAGI TCP server."""
    try:
        server = await asyncio.start_server(handle_connection, host, port)
        addr = server.sockets[0].getsockname()
        logger.info(f"FastAGI server listening on {addr[0]}:{addr[1]}")

        async with server:
            await server.serve_forever()
    except OSError as e:
        if e.errno == 98:
            # Another worker already bound this port — that's OK
            logger.info(f"FastAGI port {port} already in use (another worker has it)")
        else:
            logger.error(f"FastAGI server failed to start: {e}")

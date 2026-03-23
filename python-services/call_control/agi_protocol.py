"""
FastAGI Protocol Handler.

Implements the Asterisk AGI wire protocol over TCP sockets.
Port of app/Services/Agi/AgiConnection.php

Wire format:
- Lines terminated with \n
- Environment: "agi_KEY: VALUE" lines, blank line terminates
- Commands: "COMMAND arg1 arg2\n"
- Responses: "200 result=VALUE (optional data)"
"""

import asyncio
import logging
import re
from typing import Optional

logger = logging.getLogger(__name__)


class AgiConnection:
    """
    Handles a single AGI connection from Asterisk.

    Usage:
        conn = AgiConnection(reader, writer)
        await conn.parse()
        script = conn.get_script()
        # Process call...
        await conn.set_variable("ROUTE_ACTION", "DIAL")
    """

    def __init__(self, reader: asyncio.StreamReader, writer: asyncio.StreamWriter):
        self.reader = reader
        self.writer = writer
        self.env: dict[str, str] = {}
        self._closed = False

    async def parse(self) -> None:
        """Read AGI environment variables until blank line."""
        while True:
            line = await self._read_line()
            if line is None or line.strip() == "":
                break
            # Format: "agi_key: value"
            if ":" in line:
                key, _, value = line.partition(":")
                self.env[key.strip()] = value.strip()

        logger.debug(f"AGI env: channel={self.get_channel()}, ext={self.get_extension()}")

    def get_env(self, key: str, default: str = "") -> str:
        return self.env.get(key, default)

    def get_script(self) -> str:
        """Extract script name from agi_request URL (e.g., agi://host:port/route_outbound)."""
        request = self.env.get("agi_request", "")
        # Extract path after last /
        if "/" in request:
            return request.rstrip("/").rsplit("/", 1)[-1]
        return request

    def get_channel(self) -> str:
        return self.env.get("agi_channel", "")

    def get_extension(self) -> str:
        return self.env.get("agi_extension", "")

    def get_caller_id(self) -> str:
        return self.env.get("agi_callerid", "")

    def get_caller_id_name(self) -> str:
        return self.env.get("agi_calleridname", "")

    def get_unique_id(self) -> str:
        return self.env.get("agi_uniqueid", "")

    def get_context(self) -> str:
        return self.env.get("agi_context", "")

    async def command(self, cmd: str) -> str:
        """Send AGI command and return response."""
        await self._write_line(cmd)
        response = await self._read_line()
        return response or ""

    async def set_variable(self, name: str, value: str) -> None:
        """SET VARIABLE name value."""
        # Quote value if it contains spaces
        if " " in str(value) or '"' in str(value):
            safe_value = str(value).replace('"', '\\"')
            await self.command(f'SET VARIABLE {name} "{safe_value}"')
        else:
            await self.command(f"SET VARIABLE {name} {value}")

    async def get_variable(self, name: str) -> Optional[str]:
        """GET VARIABLE name. Returns value or None."""
        response = await self.command(f"GET VARIABLE {name}")
        # Response format: "200 result=1 (value)" or "200 result=0"
        match = re.search(r"result=1 \((.+)\)", response)
        if match:
            return match.group(1)
        return None

    async def exec(self, application: str, *args: str) -> str:
        """EXEC application arg1,arg2,..."""
        arg_str = ",".join(str(a) for a in args) if args else ""
        return await self.command(f"EXEC {application} {arg_str}")

    async def verbose(self, message: str, level: int = 1) -> None:
        """VERBOSE message level."""
        await self.command(f'VERBOSE "{message}" {level}')

    async def answer(self) -> None:
        """Answer the channel."""
        await self.command("ANSWER")

    async def hangup(self) -> None:
        """Hang up the channel."""
        await self.command("HANGUP")

    def close(self) -> None:
        """Close the connection."""
        if not self._closed:
            self._closed = True
            try:
                self.writer.close()
            except Exception:
                pass

    async def _read_line(self) -> Optional[str]:
        try:
            data = await asyncio.wait_for(self.reader.readline(), timeout=30)
            if not data:
                return None
            return data.decode("utf-8").rstrip("\r\n")
        except (asyncio.TimeoutError, ConnectionResetError, BrokenPipeError):
            return None

    async def _write_line(self, line: str) -> None:
        try:
            self.writer.write(f"{line}\n".encode("utf-8"))
            await self.writer.drain()
        except (ConnectionResetError, BrokenPipeError):
            self._closed = True

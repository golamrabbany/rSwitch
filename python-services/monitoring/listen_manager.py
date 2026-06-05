"""Live-listen session management + AudioSocket TCP server.

Each listen session opens one or two ChanSpy/AudioSocket legs (left = caller,
right = callee). Asterisk connects OUT to AudioSocketServer; we relay each
leg's SLIN PCM to the browser WebSocket prefixed with a 1-byte side marker
(0x00 = left, 0x01 = right). Closing the AudioSocket writer ends the spy.
"""
import asyncio
import logging
import time
from typing import Callable, Optional

from shared.audiosocket_protocol import (
    AudioSocketFrameDecoder,
    TYPE_UUID,
    TYPE_AUDIO,
    TYPE_TERMINATE,
    uuid_bytes_to_str,
)

logger = logging.getLogger(__name__)

SIDE_LEFT = b"\x00"
SIDE_RIGHT = b"\x01"


class ListenSession:
    __slots__ = ["ws", "left_uuid", "right_uuid", "uid", "linked_id",
                 "left_writer", "right_writer", "started_at"]

    def __init__(self, ws, left_uuid, right_uuid, uid, linked_id):
        self.ws = ws
        self.left_uuid = left_uuid
        self.right_uuid = right_uuid
        self.uid = uid
        self.linked_id = linked_id
        self.left_writer = None
        self.right_writer = None
        self.started_at = time.time()


class ListenSessionManager:
    def __init__(self, max_sessions: int = 3,
                 audit_writer: Optional[Callable[[int, str, int], None]] = None,
                 clock: Callable[[], float] = time.time):
        self.max_sessions = max_sessions
        self._audit_writer = audit_writer or (lambda uid, linked_id, seconds: None)
        self._clock = clock
        self._sessions: dict[str, ListenSession] = {}
        self._uuid_index: dict[str, tuple[str, str]] = {}  # as_uuid -> (session_id, 'L'|'R')

    def can_start(self) -> bool:
        return len(self._sessions) < self.max_sessions

    def create(self, session_id, ws, left_uuid, right_uuid, uid, linked_id) -> ListenSession:
        s = ListenSession(ws, left_uuid, right_uuid, uid, linked_id)
        self._sessions[session_id] = s
        self._uuid_index[left_uuid] = (session_id, "L")
        if right_uuid:
            self._uuid_index[right_uuid] = (session_id, "R")
        return s

    def on_connect(self, as_uuid, writer):
        idx = self._uuid_index.get(as_uuid)
        if not idx:
            return
        session_id, side = idx
        s = self._sessions.get(session_id)
        if not s:
            return
        if side == "L":
            s.left_writer = writer
        else:
            s.right_writer = writer

    async def on_audio(self, as_uuid, pcm: bytes):
        idx = self._uuid_index.get(as_uuid)
        if not idx:
            return
        session_id, side = idx
        s = self._sessions.get(session_id)
        if not s:
            return
        marker = SIDE_LEFT if side == "L" else SIDE_RIGHT
        try:
            await s.ws.send_bytes(marker + pcm)
        except Exception:
            pass

    def session_id_for_uuid(self, as_uuid) -> Optional[str]:
        idx = self._uuid_index.get(as_uuid)
        return idx[0] if idx else None

    async def end_from_call(self, session_id):
        """The Asterisk call ended (AudioSocket closed): notify the browser so
        it can auto-close, close its WebSocket, then tear down."""
        s = self._sessions.get(session_id)
        if s is not None:
            try:
                await s.ws.send_json({"type": "call_ended"})
            except Exception:
                pass
            try:
                await s.ws.close()
            except Exception:
                pass
        await self.teardown(session_id)

    async def teardown(self, session_id):
        s = self._sessions.pop(session_id, None)
        if not s:
            return
        for w in (s.left_writer, s.right_writer):
            if w is not None:
                try:
                    w.close()
                except Exception:
                    pass
        self._uuid_index.pop(s.left_uuid, None)
        if s.right_uuid:
            self._uuid_index.pop(s.right_uuid, None)
        seconds = int(self._clock() - s.started_at)
        try:
            self._audit_writer(s.uid, s.linked_id, seconds)
        except Exception as e:
            logger.warning(f"listen-stop audit failed: {e}")


class AudioSocketServer:
    """asyncio TCP server that Asterisk's AudioSocket channel connects to."""

    def __init__(self, host, port, manager: ListenSessionManager,
                 on_socket_close: Callable[[str], None]):
        self._host = host
        self._port = port
        self._manager = manager
        self._on_socket_close = on_socket_close
        self._server = None

    async def start(self):
        self._server = await asyncio.start_server(
            self._handle, self._host, self._port
        )
        logger.info(f"AudioSocket server listening on {self._host}:{self._port}")

    async def _handle(self, reader, writer):
        decoder = AudioSocketFrameDecoder()
        as_uuid = None
        try:
            while True:
                data = await reader.read(4096)
                if not data:
                    break
                for ftype, payload in decoder.feed(data):
                    if ftype == TYPE_UUID:
                        as_uuid = uuid_bytes_to_str(payload)
                        self._manager.on_connect(as_uuid, writer)
                    elif ftype == TYPE_AUDIO and as_uuid:
                        await self._manager.on_audio(as_uuid, payload)
                    elif ftype == TYPE_TERMINATE:
                        break
        except Exception as e:
            logger.debug(f"AudioSocket connection error: {e}")
        finally:
            try:
                writer.close()
            except Exception:
                pass
            if as_uuid:
                self._on_socket_close(as_uuid)

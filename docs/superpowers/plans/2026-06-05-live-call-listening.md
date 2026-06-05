# Live Call Listening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a super admin silently listen to a live call in the browser from the Active Calls page, with the caller on the left audio channel and the callee on the right, plus a live stereo sound-bar visualizer.

**Architecture:** Browser clicks Listen → Laravel mints a short-lived HMAC token (super-admin gated, audited) → browser opens `wss://…/ws/listen?token=…` → the Python engine validates the token, resolves the call's two legs via `linked_id`, and fires two AMI `Originate` actions that each run `ChanSpy(<leg-channel>,qoS)` on an `AudioSocket/127.0.0.1:4574/<uuid>` channel. Asterisk connects out to the engine's AudioSocket TCP server; the engine relays each leg's SLIN PCM frames to the browser tagged left/right; the browser plays them through a stereo AudioWorklet. Closing the WebSocket closes the AudioSocket sockets, which tears down the spy channels.

**Tech Stack:** Asterisk 20.11 (ChanSpy + AudioSocket), Python FastAPI/asyncio + panoramisk (engine), Laravel 12 (token + audit), vanilla JS + AudioWorklet + Web Audio API (browser), Alpine.js modal.

---

## File Structure

**Python engine (`python-services/`)**
- Create `shared/audiosocket_protocol.py` — AudioSocket frame decoder + UUID helper (pure logic).
- Create `shared/listen_auth.py` — HMAC listen-token verification (pure logic).
- Create `monitoring/listen_manager.py` — `ListenSession`, `ListenSessionManager`, `AudioSocketServer`.
- Modify `monitoring/ami_listener.py` — add `get_call_legs()` and `originate_chanspy()`.
- Modify `shared/config.py` — add `listen_token_secret` field.
- Modify `main.py` — start the AudioSocket server in lifespan; add `/ws/listen` route.
- Create `tests/` (+ `conftest.py`, `pytest.ini`) — new pytest harness.
- Modify `requirements.txt` — add `pytest`, `pytest-asyncio`.

**Laravel**
- Create `app/Services/ListenTokenService.php` — HMAC sign/verify.
- Create `app/Http/Controllers/Admin/LiveListenController.php` — token endpoint.
- Modify `config/services.php` — add `listen.token_secret`.
- Modify `routes/web.php` — add the super-admin token route.
- Create `tests/Feature/Admin/LiveListenTest.php` — authorization + audit tests.
- Create `tests/Unit/ListenTokenServiceTest.php` — sign/verify round-trip.

**Frontend**
- Modify `resources/views/admin/operational-reports/active-calls.blade.php` — Listen column + button (super-admin gated), Alpine listen modal, WS audio client, AudioWorklet loader, stereo visualizer.
- Create `public/js/listen-worklet.js` — AudioWorklet processor (stereo PCM playback).

**Installer / Asterisk**
- Modify `installer/install.sh` — remove 3 audiosocket noloads (lines 979, 986, 1063); generate + write `LISTEN_TOKEN_SECRET` to Laravel `.env` and engine `.env`.
- Modify `installer/install-engine.sh` — remove 3 audiosocket noloads (lines 481, 488, 565); write `LISTEN_TOKEN_SECRET` to engine `.env`.

---

## Phase A — Engine pure-logic units (TDD)

### Task 1: Introduce the pytest harness

**Files:**
- Modify: `python-services/requirements.txt`
- Create: `python-services/pytest.ini`
- Create: `python-services/tests/__init__.py`
- Create: `python-services/tests/conftest.py`

- [ ] **Step 1: Add test dependencies**

Append to `python-services/requirements.txt`:

```
pytest==8.3.4
pytest-asyncio==0.25.2
```

- [ ] **Step 2: Create pytest config**

Create `python-services/pytest.ini`:

```ini
[pytest]
asyncio_mode = auto
testpaths = tests
python_files = test_*.py
```

- [ ] **Step 3: Create test package + conftest**

Create `python-services/tests/__init__.py` (empty file).

Create `python-services/tests/conftest.py`:

```python
import os
import sys

# Make the engine package importable as `shared.*`, `monitoring.*`, etc.
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
```

- [ ] **Step 4: Install and verify pytest collects nothing yet**

Run (from `python-services/`): `python3 -m pip install -r requirements.txt && python3 -m pytest -q`
Expected: `no tests ran` (exit 5) — harness works, no tests yet.

- [ ] **Step 5: Commit**

```bash
git add python-services/requirements.txt python-services/pytest.ini python-services/tests/
git commit -m "test: add pytest harness for python engine"
```

---

### Task 2: AudioSocket frame decoder

The engine receives a TCP byte stream from Asterisk's AudioSocket. Each frame is `[1-byte type][2-byte big-endian length][payload]`. Type `0x01` carries a 16-byte UUID, `0x10` carries SLIN PCM audio, `0x00` signals terminate. The decoder must reassemble frames across arbitrary TCP read boundaries.

**Files:**
- Create: `python-services/shared/audiosocket_protocol.py`
- Test: `python-services/tests/test_audiosocket_protocol.py`

- [ ] **Step 1: Write the failing test**

Create `python-services/tests/test_audiosocket_protocol.py`:

```python
import uuid

from shared.audiosocket_protocol import (
    AudioSocketFrameDecoder,
    TYPE_UUID,
    TYPE_AUDIO,
    TYPE_TERMINATE,
    uuid_bytes_to_str,
)


def _frame(ftype, payload):
    return bytes([ftype, (len(payload) >> 8) & 0xFF, len(payload) & 0xFF]) + payload


def test_decodes_single_audio_frame():
    dec = AudioSocketFrameDecoder()
    pcm = b"\x01\x02" * 160  # 320 bytes = 20ms SLIN
    frames = dec.feed(_frame(TYPE_AUDIO, pcm))
    assert frames == [(TYPE_AUDIO, pcm)]


def test_decodes_uuid_frame():
    dec = AudioSocketFrameDecoder()
    u = uuid.uuid4()
    frames = dec.feed(_frame(TYPE_UUID, u.bytes))
    assert frames == [(TYPE_UUID, u.bytes)]
    assert uuid_bytes_to_str(frames[0][1]) == str(u)


def test_reassembles_frame_split_across_feeds():
    dec = AudioSocketFrameDecoder()
    full = _frame(TYPE_AUDIO, b"\xAA" * 100)
    assert dec.feed(full[:5]) == []          # header + partial payload
    assert dec.feed(full[5:]) == [(TYPE_AUDIO, b"\xAA" * 100)]


def test_decodes_multiple_frames_in_one_feed():
    dec = AudioSocketFrameDecoder()
    blob = _frame(TYPE_AUDIO, b"\x11" * 4) + _frame(TYPE_TERMINATE, b"")
    assert dec.feed(blob) == [(TYPE_AUDIO, b"\x11" * 4), (TYPE_TERMINATE, b"")]
```

- [ ] **Step 2: Run test to verify it fails**

Run: `python3 -m pytest tests/test_audiosocket_protocol.py -q`
Expected: FAIL — `ModuleNotFoundError: No module named 'shared.audiosocket_protocol'`.

- [ ] **Step 3: Write minimal implementation**

Create `python-services/shared/audiosocket_protocol.py`:

```python
"""AudioSocket wire-protocol decoder.

AudioSocket frames: [1 byte type][2 bytes big-endian length][payload].
See https://docs.asterisk.org/ (res_audiosocket).
"""
import uuid

TYPE_TERMINATE = 0x00
TYPE_UUID = 0x01
TYPE_AUDIO = 0x10
TYPE_ERROR = 0xFF


class AudioSocketFrameDecoder:
    """Stateful decoder that reassembles frames across TCP read boundaries."""

    def __init__(self):
        self._buf = bytearray()

    def feed(self, data: bytes):
        """Append bytes, return a list of complete (type, payload) frames."""
        self._buf.extend(data)
        frames = []
        while len(self._buf) >= 3:
            ftype = self._buf[0]
            length = (self._buf[1] << 8) | self._buf[2]
            if len(self._buf) < 3 + length:
                break
            payload = bytes(self._buf[3:3 + length])
            frames.append((ftype, payload))
            del self._buf[:3 + length]
        return frames


def uuid_bytes_to_str(payload: bytes) -> str:
    """Convert a 16-byte AudioSocket UUID payload to its canonical string form."""
    return str(uuid.UUID(bytes=payload))
```

- [ ] **Step 4: Run test to verify it passes**

Run: `python3 -m pytest tests/test_audiosocket_protocol.py -q`
Expected: PASS (4 passed).

- [ ] **Step 5: Commit**

```bash
git add python-services/shared/audiosocket_protocol.py python-services/tests/test_audiosocket_protocol.py
git commit -m "feat(engine): AudioSocket frame decoder"
```

---

### Task 3: Listen-token verification

Laravel signs a compact token `base64url(json_payload).base64url(hmac_sha256(secret, base64url(json_payload)))`. The engine verifies the signature over the **received** message string (never re-serializing JSON, so there is no canonicalization mismatch), checks expiry and the `super_admin` role, and returns the claims.

**Files:**
- Create: `python-services/shared/listen_auth.py`
- Test: `python-services/tests/test_listen_auth.py`

- [ ] **Step 1: Write the failing test**

Create `python-services/tests/test_listen_auth.py`:

```python
import base64
import hashlib
import hmac
import json

from shared.listen_auth import verify_listen_token

SECRET = "test-secret-123"


def _b64url(raw: bytes) -> str:
    return base64.urlsafe_b64encode(raw).rstrip(b"=").decode()


def _make_token(payload: dict, secret: str = SECRET) -> str:
    msg = _b64url(json.dumps(payload).encode())
    sig = _b64url(hmac.new(secret.encode(), msg.encode(), hashlib.sha256).digest())
    return f"{msg}.{sig}"


def test_valid_token_returns_claims():
    payload = {"lid": "1700000000.5", "uid": 15192, "role": "super_admin", "exp": 9999999999}
    claims = verify_listen_token(_make_token(payload), SECRET, now=1000)
    assert claims["lid"] == "1700000000.5"
    assert claims["uid"] == 15192


def test_expired_token_rejected():
    payload = {"lid": "x", "uid": 1, "role": "super_admin", "exp": 500}
    assert verify_listen_token(_make_token(payload), SECRET, now=1000) is None


def test_wrong_role_rejected():
    payload = {"lid": "x", "uid": 1, "role": "admin", "exp": 9999999999}
    assert verify_listen_token(_make_token(payload), SECRET, now=1000) is None


def test_bad_signature_rejected():
    payload = {"lid": "x", "uid": 1, "role": "super_admin", "exp": 9999999999}
    token = _make_token(payload, secret="WRONG")
    assert verify_listen_token(token, SECRET, now=1000) is None


def test_malformed_token_rejected():
    assert verify_listen_token("not-a-token", SECRET, now=1000) is None
    assert verify_listen_token("", SECRET, now=1000) is None
```

- [ ] **Step 2: Run test to verify it fails**

Run: `python3 -m pytest tests/test_listen_auth.py -q`
Expected: FAIL — `ModuleNotFoundError: No module named 'shared.listen_auth'`.

- [ ] **Step 3: Write minimal implementation**

Create `python-services/shared/listen_auth.py`:

```python
"""Verification for the live-listen HMAC token minted by Laravel."""
import base64
import hashlib
import hmac
import json
from typing import Optional


def _b64url_decode(s: str) -> bytes:
    pad = "=" * (-len(s) % 4)
    return base64.urlsafe_b64decode(s + pad)


def verify_listen_token(token: str, secret: str, now: int) -> Optional[dict]:
    """Return the token claims if valid, else None.

    Valid means: well-formed, HMAC-SHA256 signature matches `secret`,
    not expired (claims['exp'] >= now), and role == 'super_admin'.
    """
    if not token or not secret:
        return None
    try:
        msg, sig = token.split(".", 1)
    except ValueError:
        return None
    expected = base64.urlsafe_b64encode(
        hmac.new(secret.encode(), msg.encode(), hashlib.sha256).digest()
    ).rstrip(b"=").decode()
    if not hmac.compare_digest(sig, expected):
        return None
    try:
        claims = json.loads(_b64url_decode(msg))
    except (ValueError, json.JSONDecodeError):
        return None
    if int(claims.get("exp", 0)) < now:
        return None
    if claims.get("role") != "super_admin":
        return None
    return claims
```

- [ ] **Step 4: Run test to verify it passes**

Run: `python3 -m pytest tests/test_listen_auth.py -q`
Expected: PASS (5 passed).

- [ ] **Step 5: Commit**

```bash
git add python-services/shared/listen_auth.py python-services/tests/test_listen_auth.py
git commit -m "feat(engine): live-listen HMAC token verification"
```

---

### Task 4: Resolve call legs and map to left/right

Add `get_call_legs(linked_id)` to `AMIListener`. It returns `(caller_leg, callee_leg)` where the caller leg is the one whose `unique_id == linked_id` (the Asterisk originator) and maps to the **left** audio channel; the other leg maps to **right**. Returns `None` if no legs exist, and `callee_leg is None` if the call is not yet bridged (only one leg).

**Files:**
- Modify: `python-services/monitoring/ami_listener.py` (add method to `AMIListener`)
- Test: `python-services/tests/test_get_call_legs.py`

- [ ] **Step 1: Write the failing test**

Create `python-services/tests/test_get_call_legs.py`:

```python
from monitoring.ami_listener import AMIListener, ActiveCall


def _call(unique_id, linked_id, channel):
    c = ActiveCall(unique_id, channel)
    c.linked_id = linked_id
    return c


def test_caller_leg_is_left_callee_is_right():
    ami = AMIListener()
    a = _call("100.1", "100.1", "PJSIP/alice-0001")   # originator → left
    b = _call("100.2", "100.1", "PJSIP/trunk1-0002")  # other leg → right
    ami._active_calls = {"100.1": a, "100.2": b}

    caller_leg, callee_leg = ami.get_call_legs("100.1")
    assert caller_leg.channel == "PJSIP/alice-0001"
    assert callee_leg.channel == "PJSIP/trunk1-0002"


def test_single_leg_returns_no_callee():
    ami = AMIListener()
    a = _call("200.1", "200.1", "PJSIP/bob-0001")
    ami._active_calls = {"200.1": a}

    caller_leg, callee_leg = ami.get_call_legs("200.1")
    assert caller_leg.channel == "PJSIP/bob-0001"
    assert callee_leg is None


def test_unknown_linked_id_returns_none():
    ami = AMIListener()
    ami._active_calls = {}
    assert ami.get_call_legs("nope") is None
```

> Note: `AMIListener()` only constructs state in `__init__` (it does not connect until `await connect()`), so it is safe to instantiate in a unit test. Verify `ActiveCall.__init__(self, unique_id, channel)` matches the actual signature in `ami_listener.py` (lines 29–41); adjust the `_call` helper if the constructor differs.

- [ ] **Step 2: Run test to verify it fails**

Run: `python3 -m pytest tests/test_get_call_legs.py -q`
Expected: FAIL — `AttributeError: 'AMIListener' object has no attribute 'get_call_legs'`.

- [ ] **Step 3: Add the method**

In `python-services/monitoring/ami_listener.py`, add this method to the `AMIListener` class (place it near the other active-call accessors such as `get_active_calls_list`):

```python
    def get_call_legs(self, linked_id: str):
        """Return (caller_leg, callee_leg) ActiveCall objects for a call.

        The caller leg is the Asterisk originator (unique_id == linked_id) and
        maps to the LEFT audio channel; the other leg maps to RIGHT. Returns
        None if no legs exist; callee_leg is None if the call has only one leg.
        """
        legs = [c for c in self._active_calls.values() if c.linked_id == linked_id]
        if not legs:
            return None
        caller_leg = next((c for c in legs if c.unique_id == c.linked_id), legs[0])
        callee_leg = next((c for c in legs if c is not caller_leg), None)
        return caller_leg, callee_leg
```

- [ ] **Step 4: Run test to verify it passes**

Run: `python3 -m pytest tests/test_get_call_legs.py -q`
Expected: PASS (3 passed).

- [ ] **Step 5: Commit**

```bash
git add python-services/monitoring/ami_listener.py python-services/tests/test_get_call_legs.py
git commit -m "feat(engine): resolve call legs to left/right for live listen"
```

---

## Phase B — Engine listen manager, AudioSocket server, AMI originate, WS route

### Task 5: ListenSessionManager + AudioSocketServer

`ListenSessionManager` maps each spy `audiosocket_uuid → (session, side)` and relays PCM to the browser WebSocket tagged with a 1-byte side marker (`0x00`=left, `0x01`=right). `AudioSocketServer` is an asyncio TCP server Asterisk connects out to; it decodes frames and drives the manager. Teardown closes the AudioSocket sockets (which ends the ChanSpy channels) and writes the `call.listen.stop` audit row.

**Files:**
- Create: `python-services/monitoring/listen_manager.py`
- Test: `python-services/tests/test_listen_manager.py`

- [ ] **Step 1: Write the failing test**

Create `python-services/tests/test_listen_manager.py`:

```python
import pytest

from monitoring.listen_manager import ListenSessionManager


class FakeWS:
    def __init__(self):
        self.sent = []

    async def send_bytes(self, data):
        self.sent.append(data)


class FakeWriter:
    def __init__(self):
        self.closed = False

    def close(self):
        self.closed = True


def test_can_start_respects_cap():
    mgr = ListenSessionManager(max_sessions=2, audit_writer=lambda *a, **k: None)
    assert mgr.can_start() is True
    mgr.create("s1", FakeWS(), "L1", "R1", uid=1, linked_id="a")
    mgr.create("s2", FakeWS(), "L2", "R2", uid=1, linked_id="b")
    assert mgr.can_start() is False


@pytest.mark.asyncio
async def test_audio_is_tagged_left_and_right():
    mgr = ListenSessionManager(max_sessions=3, audit_writer=lambda *a, **k: None)
    ws = FakeWS()
    mgr.create("s1", ws, "L-uuid", "R-uuid", uid=1, linked_id="a")

    await mgr.on_audio("L-uuid", b"\xAA\xAA")
    await mgr.on_audio("R-uuid", b"\xBB\xBB")

    assert ws.sent[0] == b"\x00\xAA\xAA"   # left marker
    assert ws.sent[1] == b"\x01\xBB\xBB"   # right marker


@pytest.mark.asyncio
async def test_teardown_closes_writers_and_audits():
    audited = {}
    mgr = ListenSessionManager(
        max_sessions=3,
        audit_writer=lambda uid, linked_id, seconds: audited.update(
            uid=uid, linked_id=linked_id, seconds=seconds
        ),
    )
    mgr.create("s1", FakeWS(), "L-uuid", "R-uuid", uid=42, linked_id="call-9")
    lw, rw = FakeWriter(), FakeWriter()
    mgr.on_connect("L-uuid", lw)
    mgr.on_connect("R-uuid", rw)

    await mgr.teardown("s1")

    assert lw.closed and rw.closed
    assert audited["uid"] == 42 and audited["linked_id"] == "call-9"
    assert mgr.can_start() is True  # session removed
```

- [ ] **Step 2: Run test to verify it fails**

Run: `python3 -m pytest tests/test_listen_manager.py -q`
Expected: FAIL — `ModuleNotFoundError: No module named 'monitoring.listen_manager'`.

- [ ] **Step 3: Write the implementation**

Create `python-services/monitoring/listen_manager.py`:

```python
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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `python3 -m pytest tests/test_listen_manager.py -q`
Expected: PASS (4 passed).

- [ ] **Step 5: Commit**

```bash
git add python-services/monitoring/listen_manager.py python-services/tests/test_listen_manager.py
git commit -m "feat(engine): listen session manager + AudioSocket server"
```

---

### Task 6: AMI `originate_chanspy` method

Add a method on `AMIListener` to issue the AMI `Originate` that creates an `AudioSocket` channel running `ChanSpy(<target>,qoS)` — `q` (quiet, no spoken prompts on the spy leg), `o` (one-directional: capture only the audio coming **from** the target channel = that party's voice), `S` (stop when the spied channel goes away). The parties never hear anything because ChanSpy in listen/one-way mode injects no audio toward them.

**Files:**
- Modify: `python-services/monitoring/ami_listener.py`
- Test: `python-services/tests/test_originate_chanspy.py`

- [ ] **Step 1: Write the failing test**

Create `python-services/tests/test_originate_chanspy.py`:

```python
import pytest

from monitoring.ami_listener import AMIListener


class FakeManager:
    def __init__(self):
        self.actions = []

    async def send_action(self, action):
        self.actions.append(action)
        return []


@pytest.mark.asyncio
async def test_originate_chanspy_builds_correct_action():
    ami = AMIListener()
    ami.manager = FakeManager()

    await ami.originate_chanspy("PJSIP/alice-0001", "uuid-123", "127.0.0.1", 4574)

    action = ami.manager.actions[0]
    assert action["Action"] == "Originate"
    assert action["Channel"] == "AudioSocket/127.0.0.1:4574/uuid-123"
    assert action["Application"] == "ChanSpy"
    assert action["Data"] == "PJSIP/alice-0001,qoS"
    assert action["Async"] == "true"
```

- [ ] **Step 2: Run test to verify it fails**

Run: `python3 -m pytest tests/test_originate_chanspy.py -q`
Expected: FAIL — `AttributeError: 'AMIListener' object has no attribute 'originate_chanspy'`.

- [ ] **Step 3: Add the method**

In `python-services/monitoring/ami_listener.py`, add to the `AMIListener` class (near the existing `send_action` usage around line 159):

```python
    async def originate_chanspy(self, target_channel: str, audiosocket_uuid: str,
                                host: str, port: int):
        """Originate an AudioSocket channel that ChanSpy's a live channel.

        ChanSpy flags: q = quiet (no prompts on the spy leg), o = one-direction
        (only audio FROM target = that party's voice), S = stop when the target
        channel is gone. Listen-only: no audio is sent to the call parties.
        """
        await self.manager.send_action({
            "Action": "Originate",
            "Channel": f"AudioSocket/{host}:{port}/{audiosocket_uuid}",
            "Application": "ChanSpy",
            "Data": f"{target_channel},qoS",
            "CallerID": "livelisten",
            "Async": "true",
            "Timeout": "30000",
        })
```

- [ ] **Step 4: Run test to verify it passes**

Run: `python3 -m pytest tests/test_originate_chanspy.py -q`
Expected: PASS (1 passed).

- [ ] **Step 5: Commit**

```bash
git add python-services/monitoring/ami_listener.py python-services/tests/test_originate_chanspy.py
git commit -m "feat(engine): AMI originate ChanSpy over AudioSocket"
```

---

### Task 7: Add `listen_token_secret` to engine config + audit-stop writer

The engine reads `LISTEN_TOKEN_SECRET` from its `.env`, and writes the `call.listen.stop` audit row directly via the sync DB engine (matching the `ami_listener.py` raw-SQL pattern). `audit_logs.user_id` is a NOT NULL FK — we use the super admin's id from the token claims.

**Files:**
- Modify: `python-services/shared/config.py`
- Create: `python-services/monitoring/listen_audit.py`
- Test: `python-services/tests/test_listen_audit_sql.py`

- [ ] **Step 1: Write the failing test (audit SQL shape)**

Create `python-services/tests/test_listen_audit_sql.py`:

```python
from monitoring.listen_audit import build_listen_stop_params


def test_build_listen_stop_params():
    params = build_listen_stop_params(uid=15192, linked_id="100.1", seconds=42)
    assert params["user_id"] == 15192
    assert params["action"] == "call.listen.stop"
    assert params["auditable_type"] == "system"
    assert params["auditable_id"] == 0
    assert '"linked_id": "100.1"' in params["new_values"]
    assert '"duration_seconds": 42' in params["new_values"]
    assert params["ip_address"] == "0.0.0.0"
    assert params["user_agent"] == "rswitch-engine"
```

- [ ] **Step 2: Run test to verify it fails**

Run: `python3 -m pytest tests/test_listen_audit_sql.py -q`
Expected: FAIL — `ModuleNotFoundError: No module named 'monitoring.listen_audit'`.

- [ ] **Step 3: Add config field + implementation**

In `python-services/shared/config.py`, add inside the `Settings` class (after `asterisk_ami_secret`):

```python
    listen_token_secret: str = ""
```

Create `python-services/monitoring/listen_audit.py`:

```python
"""Write the call.listen.stop audit row directly from the engine.

Mirrors Laravel's AuditService::log() column shape for `audit_logs`.
"""
import json
import logging

from sqlalchemy import text

from shared.database import get_sync_engine

logger = logging.getLogger(__name__)


def build_listen_stop_params(uid: int, linked_id: str, seconds: int) -> dict:
    return {
        "user_id": uid,
        "action": "call.listen.stop",
        "auditable_type": "system",
        "auditable_id": 0,
        "new_values": json.dumps({"linked_id": linked_id, "duration_seconds": seconds}),
        "ip_address": "0.0.0.0",
        "user_agent": "rswitch-engine",
    }


def write_listen_stop(uid: int, linked_id: str, seconds: int):
    """Insert a call.listen.stop audit row (best-effort)."""
    params = build_listen_stop_params(uid, linked_id, seconds)
    try:
        engine = get_sync_engine()
        with engine.connect() as conn:
            conn.execute(
                text("""
                    INSERT INTO audit_logs
                    (user_id, action, auditable_type, auditable_id,
                     old_values, new_values, ip_address, user_agent, created_at)
                    VALUES
                    (:user_id, :action, :auditable_type, :auditable_id,
                     NULL, :new_values, :ip_address, :user_agent, NOW())
                """),
                params,
            )
            conn.commit()
    except Exception as e:
        logger.warning(f"write_listen_stop failed: {e}")
```

> Note: confirm `get_sync_engine` is exported from `python-services/shared/database.py` (extraction confirmed it exists at lines 22–33). If the import name differs, match the actual export.

- [ ] **Step 4: Run test to verify it passes**

Run: `python3 -m pytest tests/test_listen_audit_sql.py -q`
Expected: PASS (1 passed).

- [ ] **Step 5: Run the full engine suite**

Run: `python3 -m pytest -q`
Expected: PASS (all tests from Tasks 2–7 green).

- [ ] **Step 6: Commit**

```bash
git add python-services/shared/config.py python-services/monitoring/listen_audit.py python-services/tests/test_listen_audit_sql.py
git commit -m "feat(engine): listen_token_secret config + listen-stop audit writer"
```

---

### Task 8: Wire the `/ws/listen` route and start the AudioSocket server

Wire everything into `main.py`: a module-level `ListenSessionManager`, an `AudioSocketServer` started in the lifespan, and the `/ws/listen` WebSocket route. This task has no unit test (it requires a live Asterisk); it is verified by the manual integration test in Task 16.

**Files:**
- Modify: `python-services/main.py`

- [ ] **Step 1: Add imports**

Near the existing imports in `python-services/main.py` (after `from monitoring.ami_listener import get_ami_listener`):

```python
import time
import uuid as uuid_mod
from monitoring.listen_manager import ListenSessionManager, AudioSocketServer
from monitoring.listen_audit import write_listen_stop
from shared.listen_auth import verify_listen_token

LISTEN_AUDIOSOCKET_HOST = "127.0.0.1"
LISTEN_AUDIOSOCKET_PORT = 4574

_listen_manager = ListenSessionManager(max_sessions=3, audit_writer=write_listen_stop)


def get_listen_manager() -> ListenSessionManager:
    return _listen_manager
```

- [ ] **Step 2: Start the AudioSocket server in the lifespan**

In the `lifespan` async context manager (after the FastAGI server is started, ~line 70), add:

```python
    # Start the AudioSocket server for live-listen
    def _on_socket_close(as_uuid: str):
        session_id = _listen_manager.session_id_for_uuid(as_uuid)
        if session_id:
            asyncio.create_task(_listen_manager.teardown(session_id))

    audiosocket_server = AudioSocketServer(
        LISTEN_AUDIOSOCKET_HOST, LISTEN_AUDIOSOCKET_PORT,
        _listen_manager, _on_socket_close,
    )
    await audiosocket_server.start()
```

- [ ] **Step 3: Add the `/ws/listen` route**

After the `/ws/live-calls` route (~line 332) in `python-services/main.py`:

```python
@app.websocket("/ws/listen")
async def websocket_listen(websocket: WebSocket):
    await websocket.accept()
    settings = get_settings()

    token = websocket.query_params.get("token", "")
    claims = verify_listen_token(token, settings.listen_token_secret, int(time.time()))
    if not claims:
        await websocket.close(code=4401)
        return

    linked_id = str(claims["lid"])
    uid = int(claims["uid"])

    ami = get_ami_listener()
    legs = ami.get_call_legs(linked_id)
    if not legs or legs[0] is None:
        await websocket.send_json({"type": "error", "reason": "call_ended"})
        await websocket.close()
        return
    caller_leg, callee_leg = legs

    mgr = get_listen_manager()
    if not mgr.can_start():
        await websocket.send_json({"type": "error", "reason": "capacity"})
        await websocket.close()
        return

    session_id = str(uuid_mod.uuid4())
    left_uuid = str(uuid_mod.uuid4())
    right_uuid = str(uuid_mod.uuid4()) if callee_leg else None
    mgr.create(session_id, websocket, left_uuid, right_uuid, uid=uid, linked_id=linked_id)

    try:
        await ami.originate_chanspy(
            caller_leg.channel, left_uuid,
            LISTEN_AUDIOSOCKET_HOST, LISTEN_AUDIOSOCKET_PORT,
        )
        if callee_leg:
            await ami.originate_chanspy(
                callee_leg.channel, right_uuid,
                LISTEN_AUDIOSOCKET_HOST, LISTEN_AUDIOSOCKET_PORT,
            )
        await websocket.send_json({"type": "listening", "stereo": callee_leg is not None})

        while True:
            try:
                data = await asyncio.wait_for(websocket.receive_text(), timeout=35.0)
                if data == "ping":
                    await websocket.send_json({"type": "pong"})
            except asyncio.TimeoutError:
                continue
    except WebSocketDisconnect:
        pass
    except Exception as e:
        logger.debug(f"listen WS error: {e}")
    finally:
        await mgr.teardown(session_id)
```

- [ ] **Step 4: Smoke-check the module imports**

Run (from `python-services/`): `python3 -c "import main"`
Expected: no `ImportError`/`SyntaxError` (it may log config warnings — that is fine).

- [ ] **Step 5: Commit**

```bash
git add python-services/main.py
git commit -m "feat(engine): /ws/listen route + AudioSocket server in lifespan"
```

---

## Phase C — Laravel token endpoint (TDD)

### Task 9: `ListenTokenService`

Mints and verifies the compact HMAC token the engine expects (must match the format verified in Task 3).

**Files:**
- Create: `app/Services/ListenTokenService.php`
- Modify: `config/services.php`
- Test: `tests/Unit/ListenTokenServiceTest.php`

- [ ] **Step 1: Add config key**

In `config/services.php`, add after the `python_api` block (~line 53):

```php
    'listen' => [
        'token_secret' => env('LISTEN_TOKEN_SECRET', ''),
    ],
```

- [ ] **Step 2: Write the failing test**

Create `tests/Unit/ListenTokenServiceTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Services\ListenTokenService;
use Tests\TestCase;

class ListenTokenServiceTest extends TestCase
{
    public function test_mint_then_verify_round_trip(): void
    {
        $svc = new ListenTokenService('secret-xyz');
        $token = $svc->mint(linkedId: '100.1', uid: 15192, ttlSeconds: 30);

        $claims = $svc->verify($token);
        $this->assertSame('100.1', $claims['lid']);
        $this->assertSame(15192, $claims['uid']);
        $this->assertSame('super_admin', $claims['role']);
    }

    public function test_tampered_token_fails_verify(): void
    {
        $svc = new ListenTokenService('secret-xyz');
        $token = $svc->mint(linkedId: '100.1', uid: 1, ttlSeconds: 30);

        $this->assertNull($svc->verify($token . 'x'));
    }

    public function test_token_has_two_dot_separated_parts(): void
    {
        $svc = new ListenTokenService('secret-xyz');
        $token = $svc->mint(linkedId: 'a', uid: 1, ttlSeconds: 30);
        $this->assertCount(2, explode('.', $token));
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

Run: `./vendor/bin/sail test tests/Unit/ListenTokenServiceTest.php`
Expected: FAIL — class `App\Services\ListenTokenService` not found.

- [ ] **Step 4: Write the implementation**

Create `app/Services/ListenTokenService.php`:

```php
<?php

namespace App\Services;

class ListenTokenService
{
    public function __construct(private string $secret)
    {
    }

    public static function fromConfig(): self
    {
        return new self((string) config('services.listen.token_secret', ''));
    }

    /** Mint a compact HMAC token: base64url(json).base64url(hmac_sha256). */
    public function mint(string $linkedId, int $uid, int $ttlSeconds = 30): string
    {
        $payload = [
            'lid' => $linkedId,
            'uid' => $uid,
            'role' => 'super_admin',
            'exp' => time() + $ttlSeconds,
        ];
        $msg = $this->b64url(json_encode($payload));
        $sig = $this->b64url(hash_hmac('sha256', $msg, $this->secret, true));

        return "{$msg}.{$sig}";
    }

    /** Return claims if valid (signature + expiry + super_admin), else null. */
    public function verify(string $token): ?array
    {
        if ($this->secret === '' || substr_count($token, '.') !== 1) {
            return null;
        }
        [$msg, $sig] = explode('.', $token, 2);
        $expected = $this->b64url(hash_hmac('sha256', $msg, $this->secret, true));
        if (!hash_equals($expected, $sig)) {
            return null;
        }
        $claims = json_decode($this->b64urlDecode($msg), true);
        if (!is_array($claims) || ($claims['exp'] ?? 0) < time()) {
            return null;
        }
        if (($claims['role'] ?? null) !== 'super_admin') {
            return null;
        }

        return $claims;
    }

    private function b64url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function b64urlDecode(string $s): string
    {
        return base64_decode(strtr($s, '-_', '+/'));
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `./vendor/bin/sail test tests/Unit/ListenTokenServiceTest.php`
Expected: PASS (3 passed).

- [ ] **Step 6: Commit**

```bash
git add app/Services/ListenTokenService.php config/services.php tests/Unit/ListenTokenServiceTest.php
git commit -m "feat: ListenTokenService for live-listen HMAC tokens"
```

---

### Task 10: `LiveListenController` + route + audit

Super-admin-gated endpoint that authorizes, writes the `call.listen.start` audit row, and returns the token. Lives in the `role:super_admin` route group.

**Files:**
- Create: `app/Http/Controllers/Admin/LiveListenController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Admin/LiveListenTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Admin/LiveListenTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LiveListenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'admin']);
        config()->set('services.listen.token_secret', 'test-secret');
    }

    public function test_super_admin_gets_token_and_audit_row(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)->postJson(
            route('admin.active-calls.listen-token'),
            ['linked_id' => '100.1', 'unique_id' => '100.1', 'caller' => '01711', 'callee' => '8801999'],
        );

        $response->assertOk()->assertJsonStructure(['token']);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'call.listen.start',
        ]);
    }

    public function test_regular_admin_is_forbidden(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)->postJson(
            route('admin.active-calls.listen-token'),
            ['linked_id' => '100.1'],
        )->assertForbidden();
    }

    public function test_requires_linked_id(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->postJson(
            route('admin.active-calls.listen-token'),
            [],
        )->assertStatus(422);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail test tests/Feature/Admin/LiveListenTest.php`
Expected: FAIL — route `admin.active-calls.listen-token` not defined.

- [ ] **Step 3: Write the controller**

Create `app/Http/Controllers/Admin/LiveListenController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Services\ListenTokenService;
use Illuminate\Http\Request;

class LiveListenController extends Controller
{
    public function token(Request $request)
    {
        // Defense-in-depth: route middleware already restricts to super_admin.
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'linked_id' => ['required', 'string', 'max:128'],
            'unique_id' => ['nullable', 'string', 'max:128'],
            'caller' => ['nullable', 'string', 'max:128'],
            'callee' => ['nullable', 'string', 'max:128'],
        ]);

        AuditService::logAction('call.listen.start', null, [
            'linked_id' => $data['linked_id'],
            'unique_id' => $data['unique_id'] ?? null,
            'caller' => $data['caller'] ?? null,
            'callee' => $data['callee'] ?? null,
        ]);

        $token = ListenTokenService::fromConfig()->mint(
            linkedId: $data['linked_id'],
            uid: (int) auth()->id(),
            ttlSeconds: 30,
        );

        return response()->json(['token' => $token]);
    }
}
```

- [ ] **Step 4: Add the route**

In `routes/web.php`, inside the `role:super_admin` group (the group starting ~line 52: `Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super_admin', 'domain:admin'])->group(...)`), add:

```php
    Route::post('active-calls/listen-token', [Admin\LiveListenController::class, 'token'])
        ->name('active-calls.listen-token');
```

- [ ] **Step 5: Run test to verify it passes**

Run: `./vendor/bin/sail test tests/Feature/Admin/LiveListenTest.php`
Expected: PASS (3 passed).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Admin/LiveListenController.php routes/web.php tests/Feature/Admin/LiveListenTest.php
git commit -m "feat: super-admin live-listen token endpoint with audit"
```

---

## Phase D — Frontend (button, modal, stereo player, visualizer)

> No automated tests — browser audio is verified in the Task 16 manual integration test. Provide complete code.

### Task 11: AudioWorklet stereo PCM player

Create the worklet processor that buffers incoming 8 kHz SLIN PCM per channel and outputs stereo (left = caller, right = callee), with a small jitter buffer. It exposes per-channel RMS via `port.postMessage` for the visualizer.

**Files:**
- Create: `public/js/listen-worklet.js`

- [ ] **Step 1: Write the worklet**

Create `public/js/listen-worklet.js`:

```js
// Stereo live-listen player. Receives {side, samples:Float32Array} via port.
// side 0 = left (caller), 1 = right (callee). Plays at the context sample rate;
// input is 8kHz so we upsample by nearest-neighbour (adequate for voice).
class ListenProcessor extends AudioWorkletProcessor {
    constructor() {
        super();
        this.left = [];
        this.right = [];
        this.inRate = 8000;
        this.ratio = this.inRate / sampleRate; // input samples consumed per output sample
        this.posL = 0;
        this.posR = 0;
        this.frameCount = 0;
        this.port.onmessage = (e) => {
            const { side, samples } = e.data;
            if (side === 0) this.left.push(...samples);
            else this.right.push(...samples);
        };
    }

    _pull(buf, posKey) {
        // Nearest-neighbour resample from 8kHz to sampleRate.
        if (buf.length === 0) return 0;
        const idx = Math.floor(this[posKey]);
        const s = buf[idx] || 0;
        this[posKey] += this.ratio;
        if (this[posKey] >= buf.length) {
            buf.splice(0, Math.floor(this[posKey]));
            this[posKey] -= Math.floor(this[posKey]);
        }
        return s;
    }

    process(inputs, outputs) {
        const out = outputs[0];
        const outL = out[0];
        const outR = out.length > 1 ? out[1] : out[0];
        let sumL = 0, sumR = 0;
        for (let i = 0; i < outL.length; i++) {
            const l = this._pull(this.left, 'posL');
            const r = this._pull(this.right, 'posR');
            outL[i] = l;
            outR[i] = r;
            sumL += l * l;
            sumR += r * r;
        }
        // Emit RMS roughly every ~10 render quanta (~26ms) for the visualizer.
        this.frameCount++;
        if (this.frameCount % 10 === 0) {
            this.port.postMessage({
                rmsL: Math.sqrt(sumL / outL.length),
                rmsR: Math.sqrt(sumR / outR.length),
            });
        }
        return true;
    }
}

registerProcessor('listen-processor', ListenProcessor);
```

- [ ] **Step 2: Commit**

```bash
git add public/js/listen-worklet.js
git commit -m "feat(ui): AudioWorklet stereo PCM player for live listen"
```

---

### Task 12: Listen button, modal, WS client + visualizer in the Active Calls page

Add a super-admin-only Listen column/button, an Alpine modal, the WS audio client, and the stereo sound-bar visualizer. The button lives in the JS-rendered rows (which carry `call.linked_id`/`call.unique_id`); a delegated click handler on the table body reads the row's data and opens the modal.

**Files:**
- Modify: `resources/views/admin/operational-reports/active-calls.blade.php`

- [ ] **Step 1: Add the "Listen" column header (super-admin only)**

In the table header row (~lines 113–122), add a final `<th>` (wrap in the Blade guard so non-super-admins never see the column):

```blade
                        @if(auth()->user()->isSuperAdmin())
                            <th class="px-3 py-2 text-left font-semibold text-gray-600">Listen</th>
                        @endif
```

- [ ] **Step 2: Expose the super-admin flag + worklet URL to JS**

Just before the `@push('scripts')` block (or at the top of it), add:

```blade
<script>
    window.IS_SUPER_ADMIN = @json(auth()->user()->isSuperAdmin());
    window.LISTEN_TOKEN_URL = "{{ route('admin.active-calls.listen-token') }}";
    window.LISTEN_WORKLET_URL = "{{ asset('js/listen-worklet.js') }}";
    window.CSRF_TOKEN = "{{ csrf_token() }}";
</script>
```

- [ ] **Step 3: Add the Listen button to the JS row template**

In the `addCallRow` function (the `tr.innerHTML` template, ~lines 373–384), append a final cell. Add the same cell to the row markup built inside `renderCallsTable` so snapshot-rendered rows also get the button (use event delegation, so no per-row listener is needed):

```js
            (window.IS_SUPER_ADMIN ? `
            <td class="px-3 py-2">
                <button type="button"
                    class="listen-btn inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50 transition-colors"
                    data-linked="${escapeHtml(call.linked_id)}"
                    data-unique="${escapeHtml(call.unique_id)}"
                    data-caller="${escapeHtml(call.caller)}"
                    data-callee="${escapeHtml(call.callee)}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M12 6.343a8 8 0 010 11.314M5 9v6h4l5 5V4L9 9H5z"/></svg>
                    Listen
                </button>
            </td>` : '')
```

> If `renderCallsTable` builds rows with a different mechanism than `addCallRow`, factor the row HTML into a shared `buildRowHtml(call)` helper and use it in both, so the Listen cell appears regardless of render path. Inspect lines ~300–390 before editing.

- [ ] **Step 4: Add the Alpine listen modal markup**

Add this Alpine block to the page body (outside the table, e.g. just before `@push('scripts')`). It matches the project modal convention (`trunk-routes/index.blade.php`):

```blade
@if(auth()->user()->isSuperAdmin())
<div x-data="listenModal()" x-cloak
     @open-listen.window="open($event.detail)">
    <div x-show="isOpen" x-cloak class="relative z-50" role="dialog" aria-modal="true"
         @keydown.escape.window="close()">
        <div x-show="isOpen" x-transition.opacity class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-start justify-center p-4 pt-16" @click="close()">
                <div x-show="isOpen" x-transition
                     @click.stop class="bg-white rounded-2xl shadow-xl w-full max-w-lg flex flex-col">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M12 6.343a8 8 0 010 11.314M5 9v6h4l5 5V4L9 9H5z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">Listen to live call</h3>
                                <p class="text-xs text-gray-500" x-text="caller + '  →  ' + callee"></p>
                            </div>
                        </div>
                        <button @click="close()" class="rounded-lg p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="px-6 py-6">
                        <p class="text-sm text-gray-600 mb-4" x-text="statusText"></p>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p class="text-xs font-medium text-gray-500 mb-2">Caller (Left)</p>
                                <div class="flex items-end gap-1 h-16">
                                    <template x-for="i in 12" :key="'l'+i">
                                        <div class="flex-1 rounded-t bg-indigo-500 transition-all duration-75"
                                             :style="`height:${barHeight(levelL, i)}%`"></div>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 mb-2">Callee (Right)</p>
                                <div class="flex items-end gap-1 h-16">
                                    <template x-for="i in 12" :key="'r'+i">
                                        <div class="flex-1 rounded-t bg-emerald-500 transition-all duration-75"
                                             :style="`height:${barHeight(levelR, i)}%`"></div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-100">
                        <button @click="close()" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100">Stop &amp; Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
```

- [ ] **Step 5: Add the delegated click handler + Alpine component**

Inside `@push('scripts')`, add a delegated listener that opens the modal, and the `listenModal()` Alpine component that owns the WS + AudioWorklet. The click handler can live in the existing IIFE; reference `callsTableBody` (already defined in the file):

```js
    // Delegated Listen-button handler (works for snapshot + event rows).
    if (window.IS_SUPER_ADMIN) {
        callsTableBody.addEventListener('click', function (e) {
            const btn = e.target.closest('.listen-btn');
            if (!btn) return;
            window.dispatchEvent(new CustomEvent('open-listen', { detail: {
                linkedId: btn.dataset.linked,
                uniqueId: btn.dataset.unique,
                caller: btn.dataset.caller,
                callee: btn.dataset.callee,
            }}));
        });
    }
```

Add the Alpine component (place in a `<script>` after the worklet globals, or inside `@push('scripts')`):

```js
function listenModal() {
    return {
        isOpen: false,
        caller: '', callee: '',
        statusText: '',
        levelL: 0, levelR: 0,
        ws: null, ctx: null, node: null,

        barHeight(level, i) {
            // 12 bars; lower bars light up first as level rises.
            const threshold = i / 12;
            const lit = Math.min(1, level * 6); // scale RMS (voice ~0.05-0.2)
            return lit >= threshold ? Math.max(8, lit * 100) : 6;
        },

        async open(detail) {
            this.caller = detail.caller || '';
            this.callee = detail.callee || '';
            this.isOpen = true;
            this.statusText = 'Connecting…';
            this.levelL = this.levelR = 0;

            // 1. Get a short-lived token from Laravel.
            let token;
            try {
                const resp = await fetch(window.LISTEN_TOKEN_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN },
                    body: JSON.stringify({
                        linked_id: detail.linkedId, unique_id: detail.uniqueId,
                        caller: detail.caller, callee: detail.callee,
                    }),
                });
                if (!resp.ok) { this.statusText = 'Not authorized.'; return; }
                token = (await resp.json()).token;
            } catch (e) { this.statusText = 'Failed to get token.'; return; }

            // 2. Set up Web Audio + worklet.
            this.ctx = new (window.AudioContext || window.webkitAudioContext)();
            await this.ctx.audioWorklet.addModule(window.LISTEN_WORKLET_URL);
            this.node = new AudioWorkletNode(this.ctx, 'listen-processor', { outputChannelCount: [2] });
            this.node.connect(this.ctx.destination);
            this.node.port.onmessage = (e) => {
                if (e.data.rmsL !== undefined) { this.levelL = e.data.rmsL; this.levelR = e.data.rmsR; }
            };

            // 3. Open the audio WebSocket.
            const wsUrl = (location.protocol === 'https:' ? 'wss://' : 'ws://')
                + location.host + '/ws/listen?token=' + encodeURIComponent(token);
            this.ws = new WebSocket(wsUrl);
            this.ws.binaryType = 'arraybuffer';
            this.ws.onmessage = (ev) => {
                if (typeof ev.data === 'string') {
                    const msg = JSON.parse(ev.data);
                    if (msg.type === 'listening') this.statusText = msg.stereo ? 'Live — stereo' : 'Live — caller only (not bridged yet)';
                    else if (msg.type === 'error') this.statusText = msg.reason === 'call_ended' ? 'Call already ended.' : (msg.reason === 'capacity' ? 'Too many active listeners.' : 'Error.');
                    else if (msg.type === 'call_ended') { this.statusText = 'Call ended.'; this._stop(); }
                    return;
                }
                // Binary: [1-byte side][SLIN16 PCM]
                const view = new DataView(ev.data);
                const side = view.getUint8(0);
                const n = (ev.data.byteLength - 1) / 2;
                const samples = new Float32Array(n);
                for (let i = 0; i < n; i++) samples[i] = view.getInt16(1 + i * 2, true) / 32768;
                if (this.node) this.node.port.postMessage({ side, samples });
            };
            this.ws.onclose = () => { if (this.isOpen) this.statusText = 'Disconnected.'; };

            // Keepalive ping.
            this._ping = setInterval(() => { if (this.ws && this.ws.readyState === 1) this.ws.send('ping'); }, 25000);
        },

        _stop() {
            clearInterval(this._ping);
            if (this.ws) { try { this.ws.close(); } catch (e) {} this.ws = null; }
            if (this.node) { try { this.node.disconnect(); } catch (e) {} this.node = null; }
            if (this.ctx) { try { this.ctx.close(); } catch (e) {} this.ctx = null; }
            this.levelL = this.levelR = 0;
        },

        close() {
            this._stop();
            this.isOpen = false;
        },
    };
}
```

- [ ] **Step 6: Build assets and render-check**

Run: `./vendor/bin/sail npm run build`
Expected: build succeeds. Then load `/admin/operational-reports/active` as a super admin and confirm the Listen column + button render and the modal opens (audio verified in Task 16). Per project memory, render-and-grep test any Blade change touching `x-data` before deploying.

- [ ] **Step 7: Commit**

```bash
git add resources/views/admin/operational-reports/active-calls.blade.php
git commit -m "feat(ui): live-listen button, modal, stereo player, sound bars"
```

---

## Phase E — Asterisk modules + installer secret

### Task 13: Enable AudioSocket modules in both installers

**Files:**
- Modify: `installer/install.sh` (lines 979, 986, 1063)
- Modify: `installer/install-engine.sh` (lines 481, 488, 565)

- [ ] **Step 1: Remove the noloads in `install.sh`**

Delete (or comment with `;`) these three lines inside the `modules.conf` heredoc:
- Line 979: `noload = chan_audiosocket.so`
- Line 986: `noload = app_audiosocket.so`
- Line 1063: `noload = res_audiosocket.so`

- [ ] **Step 2: Remove the noloads in `install-engine.sh`**

Delete (or comment) these three lines:
- Line 481: `noload = chan_audiosocket.so`
- Line 488: `noload = app_audiosocket.so`
- Line 565: `noload = res_audiosocket.so`

- [ ] **Step 3: Verify the noloads are gone**

Run: `grep -n audiosocket installer/install.sh installer/install-engine.sh`
Expected: no `noload` lines for the three audiosocket modules (any remaining matches must not be `noload`).

- [ ] **Step 4: Commit**

```bash
git add installer/install.sh installer/install-engine.sh
git commit -m "installer: enable AudioSocket modules for live call listening"
```

---

### Task 14: Generate and distribute `LISTEN_TOKEN_SECRET`

The same secret must reach the Laravel `.env` and the engine `.env`. Generate it once in `install.sh` near the AMI secret (line ~1102) and write it to both env blocks; `install-engine.sh` (engine-only box) generates/receives it for the engine `.env`.

**Files:**
- Modify: `installer/install.sh`
- Modify: `installer/install-engine.sh`

- [ ] **Step 1: Generate the secret in `install.sh`**

Near where `AMI_SECRET` is generated (line ~1102), add:

```bash
LISTEN_TOKEN_SECRET=$(openssl rand -hex 32)
```

- [ ] **Step 2: Write it to the Laravel `.env` block**

In the Laravel `.env` append (the `cat >> .env << EOF` at ~line 1324), add the dedup regex for `LISTEN_TOKEN_SECRET` to the `sed -i -E '/^(...)=/d' .env` line (~1322), then add this line inside the heredoc:

```
LISTEN_TOKEN_SECRET=${LISTEN_TOKEN_SECRET}
```

- [ ] **Step 3: Write it to the engine `.env` block in `install.sh`**

In the engine `.env` `cat > .env << EOF` block (~line 1444), add:

```
LISTEN_TOKEN_SECRET=${LISTEN_TOKEN_SECRET}
```

- [ ] **Step 4: Handle the engine-only installer**

In `installer/install-engine.sh`, near where the engine `.env` is written (~line 770), generate the secret if not provided and write it. Since the engine box must share the App box's secret, add a prompt/variable: if an env var `LISTEN_TOKEN_SECRET` is already exported use it, else generate and print it for the operator to copy to the App box. Add before the engine `.env` heredoc:

```bash
LISTEN_TOKEN_SECRET="${LISTEN_TOKEN_SECRET:-$(openssl rand -hex 32)}"
echo "  LISTEN_TOKEN_SECRET (copy to App server .env): ${LISTEN_TOKEN_SECRET}"
```

And add inside the engine `.env` heredoc (~line 770):

```
LISTEN_TOKEN_SECRET=${LISTEN_TOKEN_SECRET}
```

- [ ] **Step 5: Surface it in the credentials file**

If the installer writes a credentials summary file (e.g. `/root/rswitch-credentials.txt`), add a `LISTEN_TOKEN_SECRET=...` line there next to the AMI secret so the operator can reconcile App + Engine boxes.

- [ ] **Step 6: Commit**

```bash
git add installer/install.sh installer/install-engine.sh
git commit -m "installer: generate + distribute LISTEN_TOKEN_SECRET"
```

---

## Phase F — Integration verification

### Task 15: Manual end-to-end integration test

No code — a verification checklist run against a real environment (dev Docker or a staging box) with AudioSocket modules loaded.

- [ ] **Step 1: Confirm modules load**

Run: `docker exec rswitch-asterisk-1 asterisk -rx "module show like audiosocket"`
Expected: `res_audiosocket.so`, `chan_audiosocket.so`, `app_audiosocket.so` all listed and `Running`. If not, `asterisk -rx "module load res_audiosocket.so"` (then chan/app) and re-check; for installer boxes confirm Task 13 removed the noloads.

- [ ] **Step 2: Confirm both env files share the secret**

Verify `LISTEN_TOKEN_SECRET` is identical in the Laravel `.env` and the engine `.env`, and that the engine process picked it up (restart the engine service after editing).

- [ ] **Step 3: Place a test call and listen**

1. Register two softphones (or use an active inbound/outbound call) so a bridged call appears on `/admin/operational-reports/active`.
2. As a super admin, click **Listen** on that row → modal opens → status reaches "Live — stereo".
3. Confirm: the **caller's** voice plays on the **left** speaker, the **callee's** on the **right**; the indigo bars react to the caller and the emerald bars to the callee.

- [ ] **Step 4: Confirm silence to parties**

On both softphones, confirm neither party hears any beep, click, or change when listening starts/stops.

- [ ] **Step 5: Confirm teardown**

1. Click **Stop & Close** → run `asterisk -rx "core show channels"` and confirm the two `AudioSocket`/ChanSpy channels are gone.
2. Repeat the listen, then hang up the call from a softphone → modal shows "Call ended" and the spy channels disappear.

- [ ] **Step 6: Confirm authorization + audit**

1. Log in as a regular admin (non-super) → confirm the Listen column/button is absent and `POST /admin/active-calls/listen-token` returns 403.
2. Query `audit_logs` and confirm a `call.listen.start` (from Laravel) and a `call.listen.stop` (from the engine, with `duration_seconds`) row exist for the test.

- [ ] **Step 7: Confirm the L/R mapping direction**

If left/right are swapped versus expectation, flip the mapping rule in `AMIListener.get_call_legs` (Task 4) — swap which leg is treated as caller/left — and re-test. (Asterisk normally sets `Linkedid == Uniqueid` on the originator, so the default rule should be correct.)

- [ ] **Step 8: Final commit (if any fixes were needed)**

```bash
git add -A
git commit -m "fix: live-listen integration adjustments"
```

---

## Self-Review Notes

- **Spec coverage:** super-admin-only (Tasks 4/9/10 + route group), fully silent (ChanSpy `qoS`, Task 6), stereo L/R (Tasks 4/5/11/12), AudioSocket transport (Tasks 2/5/6/8), token auth (Tasks 3/9/10), audit start+stop (Tasks 7/10), concurrent cap = 3 (Task 5), modules + installer + secret (Tasks 13/14), UI button+modal+visualizer (Tasks 11/12), error handling — call_ended/capacity/4401 (Task 8 route + Task 12 UI), tests (Tasks 2/3/4/5/6/7/9/10) + manual integration (Task 15).
- **Teardown** is by AudioSocket socket close (no AMI Hangup needed) — Task 5 `teardown` closes writers; Task 8 `_on_socket_close` tears down on call-end.
- **Known field-tune points** flagged inline: `ActiveCall.__init__` signature (Task 4), `renderCallsTable` row builder (Task 12 Step 3), ChanSpy flag support on the target Asterisk and L/R direction (Task 15 Steps 1/7).
- **Out of scope (per spec):** whisper/barge, reseller/client listening, recording the stream, authenticating `/ws/live-calls`, sample-accurate L/R alignment.

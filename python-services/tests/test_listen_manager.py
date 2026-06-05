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

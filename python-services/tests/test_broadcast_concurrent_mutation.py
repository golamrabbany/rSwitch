"""_broadcast must survive a client connecting/disconnecting mid-send.

The await in _broadcast yields control, so a WebSocket client can join or leave
while the loop is running. Iterating the live set raised
"RuntimeError: Set changed size during iteration", which killed the calling task
-- an _on_new_channel that died there never registered its call, so the row went
missing from Active Calls entirely.
"""

import asyncio

from monitoring.ami_listener import AMIListener


class _MutatingClient:
    """Simulates a client (dis)connecting while _broadcast is awaiting a send."""

    def __init__(self, listener, joiner):
        self.listener = listener
        self.joiner = joiner
        self.sent = 0

    async def send_json(self, message):
        self.sent += 1
        await asyncio.sleep(0)  # yield, as a real socket write does
        self.listener._ws_clients.add(self.joiner)


class _QuietClient:
    def __init__(self):
        self.sent = 0

    async def send_json(self, message):
        self.sent += 1
        await asyncio.sleep(0)


def _listener_with(clients):
    listener = AMIListener.__new__(AMIListener)  # no __init__: needs no AMI connection
    listener._ws_clients = set(clients)
    return listener


def test_client_joining_mid_broadcast_does_not_raise():
    joiner = _QuietClient()
    listener = _listener_with([])
    mutator = _MutatingClient(listener, joiner)
    listener._ws_clients.add(mutator)

    asyncio.run(listener._broadcast({"type": "ping"}))

    assert mutator.sent == 1
    assert joiner in listener._ws_clients


def test_dead_client_is_dropped():
    class _Dead:
        async def send_json(self, message):
            raise ConnectionError("gone")

    dead = _Dead()
    alive = _QuietClient()
    listener = _listener_with([dead, alive])

    asyncio.run(listener._broadcast({"type": "ping"}))

    assert dead not in listener._ws_clients
    assert alive in listener._ws_clients


def test_no_clients_is_a_noop():
    listener = _listener_with([])
    asyncio.run(listener._broadcast({"type": "ping"}))
    assert listener._ws_clients == set()

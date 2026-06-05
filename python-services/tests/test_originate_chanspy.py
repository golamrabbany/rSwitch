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

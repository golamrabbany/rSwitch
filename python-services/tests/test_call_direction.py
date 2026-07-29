"""Direction classification for Active Calls.

A trunk channel is only genuinely inbound when it is the call's ORIGINATING
channel (uniqueid == linkedid). Classifying on the channel name alone labelled
every outbound call's dialed leg "inbound", which surfaced as a phantom inbound
row whenever that leg became the displayed leg.
"""

from monitoring.ami_listener import AMIListener


class _Call:
    """Minimal stand-in for ActiveCall — only the fields _classify_direction sets."""

    def __init__(self):
        self.call_flow = ""
        self.trunk = ""
        self.sip_account = ""
        self.client = ""


def _classify(channel, uid, linked_id):
    listener = AMIListener.__new__(AMIListener)  # no __init__: needs no AMI connection
    listener._lookup_client = lambda username: f"client:{username}"
    call = _Call()
    listener._classify_direction(call, channel, uid, linked_id)
    return call


def test_dialed_trunk_leg_is_outbound_not_inbound():
    """The regression: Dial() creates this leg, so linkedid is the originator's."""
    call = _classify("PJSIP/trunk-both-4-00006401", uid="1785324125.39508",
                     linked_id="1785324125.39507")
    assert call.call_flow == "outbound"


def test_originating_trunk_channel_is_inbound():
    """A real inbound call: the trunk channel is the originator."""
    call = _classify("PJSIP/trunk-both-4-00006401", uid="1785324125.39507",
                     linked_id="1785324125.39507")
    assert call.call_flow == "inbound"
    assert call.trunk == "trunk"


def test_internal_channel_is_outbound_with_client():
    call = _classify("PJSIP/09603519000-0000719b", uid="1785325199.42497",
                     linked_id="1785325199.42497")
    assert call.call_flow == "outbound"
    assert call.sip_account == "09603519000"
    assert call.client == "client:09603519000"


def test_trunk_leg_never_sets_sip_account():
    """'trunk' is not a SIP username and must not be looked up as one."""
    call = _classify("PJSIP/trunk-both-4-00006401", uid="a.2", linked_id="a.1")
    assert call.sip_account == ""
    assert call.client == ""

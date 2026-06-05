from monitoring.ami_listener import AMIListener


def test_audiosocket_channel_is_spy():
    assert AMIListener._is_listen_spy_channel("AudioSocket/127.0.0.1:4574-00000abc") is True


def test_livelisten_callerid_is_spy():
    assert AMIListener._is_listen_spy_channel("PJSIP/x-1", "livelisten") is True


def test_real_call_is_not_spy():
    assert AMIListener._is_listen_spy_channel("PJSIP/09603100100-00000005", "09603100100") is False
    assert AMIListener._is_listen_spy_channel("PJSIP/trunk-both-4-0000000a", "") is False

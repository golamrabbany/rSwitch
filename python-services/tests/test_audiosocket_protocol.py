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

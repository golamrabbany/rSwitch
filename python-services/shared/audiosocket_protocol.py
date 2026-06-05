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

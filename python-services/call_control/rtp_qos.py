"""Per-leg RTP quality values, read from AGI variables set in the dialplan.

Asterisk exposes these through CHANNEL(rtpqos,audio,<field>), which is only
readable while the channel still exists -- hence the dialplan captures them into
variables inside a hangup handler and we read them here.

NULL vs 0 is load-bearing. NULL means the value was never captured (the leg never
came up, or the dialplan did not run); 0 means the counter really was zero, which
is exactly the no-audio signal. Anything unparseable becomes None so it cannot be
mistaken for a genuine zero.
"""

import logging
from decimal import Decimal, InvalidOperation

logger = logging.getLogger(__name__)

# DB column suffix -> Asterisk rtpqos field name.
_FIELD_MAP = (
    ("rx_count", "RXCOUNT"),
    ("tx_count", "TXCOUNT"),
    ("rx_loss", "RXPLOSS"),
    ("tx_loss", "TXPLOSS"),
    ("rx_jitter", "RXJITTER"),
    ("rtt", "RTT"),
)

RTP_COLUMN_SUFFIXES = tuple(suffix for suffix, _ in _FIELD_MAP)

_COUNT_SUFFIXES = {"rx_count", "tx_count", "rx_loss", "tx_loss"}

# Asterisk renders an unset variable in a few ways depending on context.
_EMPTY = {"", "(null)", "unknown", "nan"}


def _clean(raw):
    if raw is None:
        return None
    value = raw.strip()
    return None if value.lower() in _EMPTY else value


def parse_count(raw):
    """Packet/loss counter -> int, or None when absent or unusable."""
    value = _clean(raw)
    if value is None or not value.isdecimal():
        return None
    return int(value)


def parse_seconds(raw):
    """Jitter/RTT in seconds -> Decimal, or None when absent or unusable."""
    value = _clean(raw)
    if value is None:
        return None
    try:
        parsed = Decimal(value)
        # Reject NaN (including signed NaN) and infinity
        if parsed.is_nan() or parsed.is_infinite():
            return None
        # Reject negative values
        if parsed < 0:
            return None
    except (InvalidOperation, ValueError):
        return None
    return parsed


async def read_rtp_qos(agi, prefix: str) -> dict:
    """Read RTP_{prefix}_* AGI variables for one leg.

    Returns a dict keyed by RTP_COLUMN_SUFFIXES, with None for anything the
    dialplan did not provide. Never raises -- a leg with no stats must not break
    call teardown.
    """
    result = {}
    for suffix, field in _FIELD_MAP:
        try:
            raw = await agi.get_variable(f"RTP_{prefix}_{field}")
        except Exception as e:
            logger.warning(f"RTP QoS: could not read RTP_{prefix}_{field}: {e}")
            raw = None
        result[suffix] = (
            parse_count(raw) if suffix in _COUNT_SUFFIXES else parse_seconds(raw)
        )
    return result

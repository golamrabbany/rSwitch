"""Per-leg RTP quality values, read from AGI variables set in the dialplan.

CHANNEL(rtpqos,audio,<field>) is empty inside a hangup handler on this Asterisk
-- the RTP instance is already torn down by the time hangup handlers run
(confirmed on live production calls: all=[] lc=[] rc=[]). CHANNEL(rtcp,all,audio)
does work there, so the dialplan captures ONE variable per leg
(RTP_{prefix}_ALL) holding its semicolon-delimited `key=value` summary, e.g.:

    ssrc=810533921;themssrc=3758489602;lp=1;rxjitter=0.012000;rxcount=1516;
    txjitter=0.000125;txcount=1514;rlp=0;rtt=0.129104;rxmes=83.194000;
    txmes=82.967221

and we parse it here. The individual rtcp stats (CHANNEL(rtcp,rxcount,audio)
etc.) work too, but `lp`/`rlp` (loss) are only exposed inside the `all` summary,
so one variable plus parsing is required rather than one CHANNEL() read per
field.

NULL vs 0 is load-bearing. NULL means the value was never captured (the leg never
came up, the dialplan did not run, or that key is absent from the summary); 0
means the counter really was zero, which is exactly the no-audio signal.
Anything unparseable becomes None so it cannot be mistaken for a genuine zero.
"""

import logging
from decimal import Decimal, InvalidOperation

logger = logging.getLogger(__name__)

# DB column suffix -> key name inside the CHANNEL(rtcp,all,audio) summary.
# lp/rlp are loss counters despite the name (not "list price" or anything
# rate-related) -- Asterisk's own naming for local/remote lost packets.
_FIELD_MAP = (
    ("rx_count", "rxcount"),
    ("tx_count", "txcount"),
    ("rx_loss", "lp"),
    ("tx_loss", "rlp"),
    ("rx_jitter", "rxjitter"),
    ("rtt", "rtt"),
    # Asterisk's Media Experience Score for the audio we RECEIVED on this leg,
    # 0-100 (not the 1-5 MOS scale). Already present in every rtcp summary.
    ("rx_mes", "rxmes"),
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
    """Non-negative decimal -> Decimal, or None when absent or unusable.

    Used for jitter and RTT (seconds) and for the Media Experience Score (0-100).
    The name is historical; what it really guarantees is "a finite, non-negative
    decimal, or None". The NaN/infinity guards below exist because a signed NaN
    from Asterisk once crashed call teardown -- do not remove them, and do not
    write a second parser that lacks them.
    """
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


def _parse_summary(raw) -> dict:
    """Split a `key=value;key=value` CHANNEL(rtcp,all,audio) summary into a dict.

    Tolerant by design: this runs after the call is already over, so a garbage
    segment (no `=`, blank key, blank value) is skipped rather than raising or
    poisoning the rest of the summary. Keys are lower-cased and whitespace
    around keys/values is stripped so `rxcount = 1516` parses the same as
    `rxcount=1516`.
    """
    fields = {}
    if not raw:
        return fields
    for segment in raw.split(";"):
        if "=" not in segment:
            continue
        key, _, value = segment.partition("=")
        key = key.strip().lower()
        value = value.strip()
        if not key or not value:
            continue
        fields[key] = value
    return fields


async def read_rtp_qos(agi, prefix: str) -> dict:
    """Read the RTP_{prefix}_ALL AGI variable for one leg and parse it.

    Returns a dict keyed by RTP_COLUMN_SUFFIXES, with None for anything absent
    from -- or unparseable in -- the summary. Never raises -- a leg with no
    stats must not break call teardown.
    """
    try:
        raw = await agi.get_variable(f"RTP_{prefix}_ALL")
    except Exception as e:
        logger.warning(f"RTP QoS: could not read RTP_{prefix}_ALL: {e}")
        raw = None

    try:
        fields = _parse_summary(raw)
    except Exception as e:
        logger.warning(f"RTP QoS: could not parse RTP_{prefix}_ALL={raw!r}: {e}")
        fields = {}

    result = {}
    for suffix, key in _FIELD_MAP:
        field_raw = fields.get(key)
        result[suffix] = (
            parse_count(field_raw) if suffix in _COUNT_SUFFIXES else parse_seconds(field_raw)
        )
    return result

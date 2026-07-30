"""RTP QoS value parsing.

NULL and 0 mean different things: NULL is "not captured", 0 is "no packets
arrived". Conflating them destroys the no-audio detector, so every parse of an
absent or unusable value must yield None, never 0.

Capture mechanism (task 2b): CHANNEL(rtpqos,audio,<field>) is empty inside a
hangup handler on this Asterisk (the RTP instance is already torn down), so the
dialplan now captures a single CHANNEL(rtcp,all,audio) summary per leg into
RTP_{prefix}_ALL, and read_rtp_qos parses the semicolon-delimited key=value list
itself. The public interface (RTP_COLUMN_SUFFIXES, parse_count, parse_seconds,
read_rtp_qos's signature and six-key return shape) is unchanged.
"""

from decimal import Decimal

import pytest

from call_control.rtp_qos import RTP_COLUMN_SUFFIXES, parse_count, parse_seconds, read_rtp_qos


def test_parse_count_reads_digits():
    assert parse_count("1490") == 1490


def test_parse_count_keeps_zero_distinct_from_missing():
    assert parse_count("0") == 0          # no packets arrived -- a real finding
    assert parse_count("") is None        # not captured
    assert parse_count(None) is None
    assert parse_count("(null)") is None  # Asterisk's empty-variable rendering


def test_parse_count_rejects_garbage():
    assert parse_count("abc") is None
    assert parse_count("-5") is None      # counters cannot be negative


def test_parse_seconds_reads_decimal():
    assert parse_seconds("0.012") == Decimal("0.012")
    assert parse_seconds("0") == Decimal("0")


def test_parse_seconds_keeps_missing_as_none():
    assert parse_seconds("") is None
    assert parse_seconds(None) is None
    assert parse_seconds("nan") is None   # Asterisk emits nan for unqualified peers


def test_parse_seconds_rejects_nan_variants():
    """Regression: NaN comparison can raise InvalidOperation."""
    assert parse_seconds("-nan") is None  # signed NaN (glibc %f renders this way)
    assert parse_seconds("sNaN") is None  # signaling NaN
    assert parse_seconds("NaN123") is None  # uppercase variant with suffix


def test_parse_seconds_rejects_infinity():
    """Regression: infinity comparison can raise InvalidOperation."""
    assert parse_seconds("Infinity") is None
    assert parse_seconds("-Infinity") is None
    assert parse_seconds("inf") is None


def test_parse_count_rejects_unicode_digits():
    """Regression: isdigit() accepts Unicode digits that int() rejects."""
    assert parse_count("²") is None  # superscript 2 passes isdigit() but int() raises


class _FakeAgi:
    def __init__(self, values):
        self.values = values
        self.asked = []

    async def get_variable(self, name):
        self.asked.append(name)
        return self.values.get(name)


class _FakeAgiRaises:
    """Fake AGI that raises when get_variable is called."""

    async def get_variable(self, name):
        raise RuntimeError(f"AGI connection lost while reading {name}")


# Real production sample from CHANNEL(rtcp,all,audio), captured live in a hangup
# handler (task 2b brief). lp/rlp only exist inside this `all` summary -- the
# individual rtcp stats don't expose loss.
PROD_SAMPLE = (
    "ssrc=810533921;themssrc=3758489602;lp=1;rxjitter=0.012000;rxcount=1516;"
    "txjitter=0.000125;txcount=1514;rlp=0;rtt=0.129104;rxmes=83.194000;"
    "txmes=82.967221"
)

# Real one-way-audio sample: the callee never sent anything back. This is
# exactly the signal the feature exists to detect, so txcount=0 must survive
# as 0, not collapse into "not captured".
ONE_WAY_SAMPLE = "rxcount=843;txcount=0"


@pytest.mark.asyncio
async def test_read_rtp_qos_reads_the_single_all_variable():
    """Task 2b: one CHANNEL(rtcp,all,audio) variable per leg, not six."""
    agi = _FakeAgi({"RTP_TRUNK_ALL": PROD_SAMPLE})

    await read_rtp_qos(agi, "TRUNK")

    assert agi.asked == ["RTP_TRUNK_ALL"]


@pytest.mark.asyncio
async def test_read_rtp_qos_parses_production_sample():
    agi = _FakeAgi({"RTP_TRUNK_ALL": PROD_SAMPLE})

    result = await read_rtp_qos(agi, "TRUNK")

    assert set(result) == set(RTP_COLUMN_SUFFIXES)
    assert result["rx_count"] == 1516
    assert result["tx_count"] == 1514
    assert result["rx_loss"] == 1
    assert result["tx_loss"] == 0
    assert result["rx_jitter"] == Decimal("0.012000")
    assert result["rtt"] == Decimal("0.129104")


@pytest.mark.asyncio
async def test_read_rtp_qos_ignores_unmapped_keys():
    """ssrc/themssrc/txjitter/rxmes/txmes are in the summary but not captured."""
    agi = _FakeAgi({"RTP_CUST_ALL": PROD_SAMPLE})

    result = await read_rtp_qos(agi, "CUST")

    assert set(result) == set(RTP_COLUMN_SUFFIXES)  # only the six mapped keys


@pytest.mark.asyncio
async def test_read_rtp_qos_one_way_audio_keeps_zero_distinct():
    """The exact signal this feature exists to detect: callee sent nothing back."""
    agi = _FakeAgi({"RTP_CUST_ALL": ONE_WAY_SAMPLE})

    result = await read_rtp_qos(agi, "CUST")

    assert result["rx_count"] == 843
    assert result["tx_count"] == 0        # real zero, not None
    assert result["rx_loss"] is None      # lp not present in this summary
    assert result["tx_loss"] is None      # rlp not present in this summary
    assert result["rx_jitter"] is None
    assert result["rtt"] is None


@pytest.mark.asyncio
async def test_read_rtp_qos_returns_none_for_absent_variable():
    result = await read_rtp_qos(_FakeAgi({}), "CUST")
    assert set(result) == set(RTP_COLUMN_SUFFIXES)
    assert all(v is None for v in result.values())


@pytest.mark.asyncio
async def test_read_rtp_qos_returns_none_for_empty_string():
    result = await read_rtp_qos(_FakeAgi({"RTP_CUST_ALL": ""}), "CUST")
    assert all(v is None for v in result.values())


@pytest.mark.asyncio
async def test_read_rtp_qos_returns_none_when_no_keys_recognised():
    """A summary present but containing only ignored keys -> all six None."""
    agi = _FakeAgi({"RTP_CUST_ALL": "ssrc=810533921;themssrc=3758489602"})
    result = await read_rtp_qos(agi, "CUST")
    assert all(v is None for v in result.values())


@pytest.mark.asyncio
async def test_read_rtp_qos_missing_key_stays_none_for_that_key_only():
    """rlp (tx_loss) absent from an otherwise well-formed summary."""
    agi = _FakeAgi({"RTP_CUST_ALL": "rxcount=100;txcount=100;lp=0;rxjitter=0.01;rtt=0.05"})

    result = await read_rtp_qos(agi, "CUST")

    assert result["rx_count"] == 100
    assert result["tx_count"] == 100
    assert result["rx_loss"] == 0
    assert result["tx_loss"] is None  # rlp was never in the summary
    assert result["rx_jitter"] == Decimal("0.01")
    assert result["rtt"] == Decimal("0.05")


@pytest.mark.asyncio
async def test_read_rtp_qos_tolerates_whitespace():
    agi = _FakeAgi({"RTP_CUST_ALL": " rxcount = 1516 ; txcount=1514 ; lp=1 "})

    result = await read_rtp_qos(agi, "CUST")

    assert result["rx_count"] == 1516
    assert result["tx_count"] == 1514
    assert result["rx_loss"] == 1


@pytest.mark.asyncio
async def test_read_rtp_qos_survives_garbage_segments():
    """A malformed segment must not break parsing of the rest of the summary."""
    agi = _FakeAgi({"RTP_CUST_ALL": "foo;=5;rxcount=;txcount=1502;lp=nope;rtt=0.02"})

    result = await read_rtp_qos(agi, "CUST")

    assert result["tx_count"] == 1502
    assert result["rtt"] == Decimal("0.02")
    assert result["rx_count"] is None   # "rxcount=" has no value
    assert result["rx_loss"] is None    # "lp=nope" is not a valid count


@pytest.mark.asyncio
async def test_read_rtp_qos_survives_agi_exceptions():
    """Regression: agi.get_variable raising must not break call teardown."""
    result = await read_rtp_qos(_FakeAgiRaises(), "CUST")
    # Should return complete dict with all None, not raise
    assert set(result) == set(RTP_COLUMN_SUFFIXES)
    assert all(v is None for v in result.values())


REAL_SUMMARY_WITH_MES = (
    "ssrc=810533921;themssrc=3758489602;lp=1;rxjitter=0.012000;rxcount=1516;"
    "txjitter=0.000125;txcount=1514;rlp=0;rtt=0.129104;rxmes=83.194000;txmes=82.967221"
)


@pytest.mark.asyncio
async def test_reads_media_experience_score():
    result = await read_rtp_qos(_FakeAgi({"RTP_TRUNK_ALL": REAL_SUMMARY_WITH_MES}), "TRUNK")
    assert result["rx_mes"] == Decimal("83.194000")


@pytest.mark.asyncio
async def test_mes_absent_from_summary_is_none():
    """A summary without rxmes must yield None, not 0 -- 0 means measured-and-terrible."""
    result = await read_rtp_qos(_FakeAgi({"RTP_TRUNK_ALL": "rxcount=100;txcount=100"}), "TRUNK")
    assert result["rx_mes"] is None


@pytest.mark.asyncio
async def test_mes_zero_is_kept_distinct_from_absent():
    result = await read_rtp_qos(_FakeAgi({"RTP_TRUNK_ALL": "rxcount=100;rxmes=0"}), "TRUNK")
    assert result["rx_mes"] == Decimal("0")


def test_rx_mes_is_a_known_suffix():
    assert "rx_mes" in RTP_COLUMN_SUFFIXES

"""RTP QoS value parsing.

NULL and 0 mean different things: NULL is "not captured", 0 is "no packets
arrived". Conflating them destroys the no-audio detector, so every parse of an
absent or unusable value must yield None, never 0.
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


class _FakeAgi:
    def __init__(self, values):
        self.values = values
        self.asked = []

    async def get_variable(self, name):
        self.asked.append(name)
        return self.values.get(name)


@pytest.mark.asyncio
async def test_read_rtp_qos_maps_all_six_fields():
    agi = _FakeAgi({
        "RTP_TRUNK_RXCOUNT": "1490",
        "RTP_TRUNK_TXCOUNT": "1502",
        "RTP_TRUNK_RXPLOSS": "3",
        "RTP_TRUNK_TXPLOSS": "0",
        "RTP_TRUNK_RXJITTER": "0.012",
        "RTP_TRUNK_RTT": "0.107",
    })

    result = await read_rtp_qos(agi, "TRUNK")

    assert set(result) == set(RTP_COLUMN_SUFFIXES)
    assert result["rx_count"] == 1490
    assert result["tx_count"] == 1502
    assert result["rx_loss"] == 3
    assert result["tx_loss"] == 0
    assert result["rx_jitter"] == Decimal("0.012")
    assert result["rtt"] == Decimal("0.107")


@pytest.mark.asyncio
async def test_read_rtp_qos_returns_none_for_absent_variables():
    result = await read_rtp_qos(_FakeAgi({}), "CUST")
    assert set(result) == set(RTP_COLUMN_SUFFIXES)
    assert all(v is None for v in result.values())

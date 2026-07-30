"""The trunk-leg handler must be structurally incapable of touching billing."""

import re

import pytest

from call_control import leg_qos_handler
from call_control.leg_qos_handler import LegQosHandler

FORBIDDEN = (
    "disposition", "duration", "billsec", "status",
    "total_cost", "reseller_cost", "trunk_cost",
)


def test_updates_only_trunk_columns():
    source = open(leg_qos_handler.__file__).read()
    stmt = re.search(r"UPDATE call_records SET(.+?)WHERE uuid", source, re.S).group(1)
    for column in (
        "rtp_trunk_rx_count", "rtp_trunk_tx_count", "rtp_trunk_rx_loss",
        "rtp_trunk_tx_loss", "rtp_trunk_rx_jitter", "rtp_trunk_rtt",
    ):
        assert column in stmt
    for forbidden in FORBIDDEN:
        assert forbidden not in stmt, f"leg_qos must never write {forbidden}"


def test_update_is_bounded_by_call_start_for_partition_pruning():
    """call_records is partitioned by RANGE(TO_DAYS(call_start)) with ~125+ daily
    partitions and growing (DROP is revoked). WHERE uuid alone can't prune and
    probes every partition -- the WHERE clause must also bound call_start."""
    source = open(leg_qos_handler.__file__).read()
    where_clause = re.search(r"WHERE uuid = :uuid(.*?)\"\"\"", source, re.S).group(1)
    assert "call_start" in where_clause, "UPDATE must bound call_start so MySQL can prune partitions"


class _FakeAgi:
    def __init__(self, values):
        self.values = values
        self.verbose_calls = []

    async def get_variable(self, name):
        return self.values.get(name)

    async def verbose(self, message):
        self.verbose_calls.append(message)


class _FakeSession:
    def __init__(self):
        self.executed = []
        self.committed = False

    def execute(self, stmt, params=None):
        self.executed.append(params)

    def commit(self):
        self.committed = True


@pytest.mark.asyncio
async def test_no_cdr_uuid_is_a_noop():
    session = _FakeSession()
    await LegQosHandler().handle(_FakeAgi({}), session)
    assert session.executed == []
    assert session.committed is False


@pytest.mark.asyncio
async def test_writes_parsed_values_for_the_trunk_leg():
    agi = _FakeAgi({
        "CDR_UUID": "abc-123",
        # Single CHANNEL(rtcp,all,audio) summary (task 2b). `lp` is
        # deliberately omitted -- not set to 0 -- so trunk_rx_loss below
        # exercises "absent from the summary", not "explicit zero".
        "RTP_TRUNK_ALL": "ssrc=1;themssrc=2;rxjitter=0.01;rxcount=0;"
                          "txjitter=0.01;txcount=1502;rlp=0;rtt=0.05",
    })
    session = _FakeSession()

    await LegQosHandler().handle(agi, session)

    assert session.committed is True
    params = session.executed[0]
    assert params["uuid"] == "abc-123"
    assert params["trunk_rx_count"] == 0     # real zero: the no-audio signal
    assert params["trunk_tx_count"] == 1502
    assert params["trunk_rx_loss"] is None   # absent, not zero


@pytest.mark.asyncio
async def test_database_error_is_swallowed_not_raised():
    """The call is already over; a stats failure must never propagate."""
    class _Boom(_FakeSession):
        def execute(self, stmt, params=None):
            raise RuntimeError("db gone")

    await LegQosHandler().handle(_FakeAgi({"CDR_UUID": "abc-123"}), _Boom())


def test_update_includes_the_trunk_mes_column():
    source = open(leg_qos_handler.__file__).read()
    stmt = re.search(r"UPDATE call_records SET(.+?)WHERE uuid", source, re.S).group(1)
    assert "rtp_trunk_rx_mes" in stmt
    # still no billing column
    for forbidden in ("disposition", "duration", "billsec", "status", "total_cost"):
        assert forbidden not in stmt

"""call_end must fold the customer-leg RTP values into its existing UPDATE.

It must not issue a second statement (the point of piggybacking is to avoid an
extra round-trip), and it must never send 0 where the value was absent.
"""

import re

from call_control import call_end_handler


def test_update_statement_includes_all_six_cust_columns():
    source = open(call_end_handler.__file__).read()
    stmt = re.search(r"UPDATE call_records SET(.+?)WHERE uuid", source, re.S).group(1)
    for column in (
        "rtp_cust_rx_count", "rtp_cust_tx_count", "rtp_cust_rx_loss",
        "rtp_cust_tx_loss", "rtp_cust_rx_jitter", "rtp_cust_rtt",
    ):
        assert column in stmt, f"{column} missing from the call_end UPDATE"


def test_call_end_does_not_write_trunk_columns():
    """The trunk leg is leg_qos's job; call_end must stay out of it."""
    source = open(call_end_handler.__file__).read()
    assert "rtp_trunk_" not in source


def test_call_end_issues_only_one_update():
    source = open(call_end_handler.__file__).read()
    assert source.count("UPDATE call_records SET") == 1

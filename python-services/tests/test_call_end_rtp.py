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


def test_cust_parameters_have_no_coercion():
    """RTP values must be passed directly from read_rtp_qos without 0-fallback.

    NULL means "not captured", 0 means "no packets arrived" (the signal the
    feature detects). Any coercion like `cust["rx_count"] or 0` destroys this
    distinction and must be caught immediately.
    """
    source = open(call_end_handler.__file__).read()
    # Extract the parameter dict of the UPDATE statement
    params_match = re.search(
        r"UPDATE call_records SET.+?\},\s*\)",
        source,
        re.S,
    )
    assert params_match, "Could not find parameter dict in UPDATE statement"
    params_section = params_match.group(0)

    # Extract the six cust_ bindings and verify no coercion operators/calls
    cust_keys = ["rx_count", "tx_count", "rx_loss", "tx_loss", "rx_jitter", "rtt"]
    for key in cust_keys:
        param_name = f"cust_{key}"
        # Find the full binding line including trailing comma, up to newline or comma
        binding_match = re.search(
            rf'"{param_name}":\s*[^,]+,',
            params_section,
        )
        assert binding_match, f'Parameter "{param_name}" not found or malformed'
        binding_line = binding_match.group(0)

        # Verify the binding is exactly to cust["<key>"] with no coercion
        expected_binding = f'"{param_name}": cust["{key}"],'
        # Check if binding line matches the expected pattern (with optional whitespace)
        if not re.match(rf'"{param_name}":\s*cust\["{key}"\]\s*,', binding_line):
            assert False, f'Parameter "{param_name}" not bound directly to cust["{key}"]; got: {binding_line!r}'

        # Verify no coercion operators appear in this binding
        assert " or " not in binding_line, f'Coercion " or " found in {param_name} binding: {binding_line!r}'
        assert "int(" not in binding_line, f'Coercion int() found in {param_name} binding: {binding_line!r}'
        assert "float(" not in binding_line, f'Coercion float() found in {param_name} binding: {binding_line!r}'
        assert "?:" not in binding_line, f'Ternary ?: found in {param_name} binding: {binding_line!r}'

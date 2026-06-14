"""
Unit tests for BD national→international MSISDN normalization used in rating.

Customers dial national format (01XXXXXXXXX) but rate tables store the
international 880XXXXXXXXX prefix. Without normalization, longest-prefix
matching against an 880 rate fails and the call becomes `unbillable`
(billable_duration=0), which is the client/route duration mismatch we
are fixing.
"""

from billing.number_format import normalize_bd_msisdn


def test_national_bd_mobile_normalized_to_880():
    # 01714101351 -> 8801714101351 (strip leading 0, prepend 880)
    assert normalize_bd_msisdn("01714101351") == "8801714101351"


def test_already_international_is_untouched():
    assert normalize_bd_msisdn("8801714101351") == "8801714101351"


def test_idd_dialed_number_is_untouched():
    # 00 = international direct dialing access code; not a BD national number
    assert normalize_bd_msisdn("008801714101351") == "008801714101351"


def test_bd_landline_national_normalized():
    # 02XXXXXXXX (Dhaka landline) -> 8802XXXXXXXX
    assert normalize_bd_msisdn("0255012345") == "880255012345"


def test_non_zero_leading_number_is_untouched():
    # already without trunk prefix (e.g. some carrier paths) — leave as-is
    assert normalize_bd_msisdn("1714101351") == "1714101351"


def test_empty_and_whitespace_are_safe():
    assert normalize_bd_msisdn("") == ""
    assert normalize_bd_msisdn("  01714101351 ") == "8801714101351"

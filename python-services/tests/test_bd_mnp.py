"""
Unit tests for BD Mobile Number Portability (MNP) conversion and
malformed-number detection used on the outbound dial path.

Background: the carrier zone (e.g. RGL_DOM_2) answers '503 No Treatment
Reached' for any BD mobile number that is not MNP-prefixed. apply_bd_mnp must
therefore:
  (a) convert valid numbers to the 880 + route_code + national form,
  (b) tolerate stray separators ('-', spaces) in otherwise-valid numbers
      (the real bug — '8801730-032367' was passing through un-converted), and
  (c) leave genuinely-malformed numbers detectable via is_malformed_bd_mobile
      so the switch can reject them instead of dialing unroutable junk.

These functions are dependency-free (stdlib only) so they unit-test without
redis/sqlalchemy, mirroring normalize_bd_msisdn.
"""

from billing.number_format import apply_bd_mnp, is_malformed_bd_mobile


class TestApplyBdMnp:
    def test_valid_international_mobile_converts(self):
        assert apply_bd_mnp("8801714101351") == "880711714101351"

    def test_valid_national_mobile_converts(self):
        assert apply_bd_mnp("01714101351") == "880711714101351"

    def test_idd_prefixed_mobile_converts(self):
        assert apply_bd_mnp("008801714101351") == "880711714101351"

    def test_each_operator_maps_to_its_route_code(self):
        # 13/17 GP->71, 14/19 BL->91, 15 TT->51, 16 Airtel/18 Robi->81
        assert apply_bd_mnp("8801312345678") == "880711312345678"
        assert apply_bd_mnp("8801412345678") == "880911412345678"
        assert apply_bd_mnp("8801512345678") == "880511512345678"
        assert apply_bd_mnp("8801612345678") == "880811612345678"
        assert apply_bd_mnp("8801812345678") == "880811812345678"
        assert apply_bd_mnp("8801912345678") == "880911912345678"

    # --- the bug being fixed: stray separators in valid numbers ---
    def test_dash_separated_valid_number_converts(self):
        # Was passing through unchanged -> carrier 503 'No Treatment Reached'.
        assert apply_bd_mnp("8801730-032367") == "880711730032367"

    def test_space_separated_valid_number_converts(self):
        assert apply_bd_mnp("880 1730 032367") == "880711730032367"

    def test_plus_prefixed_with_dash_converts(self):
        assert apply_bd_mnp("+8801812-043572") == "880811812043572"

    # --- passthrough cases (must NOT be mangled) ---
    def test_landline_not_mobile_passthrough(self):
        assert apply_bd_mnp("0255012345") == "0255012345"

    def test_non_mobile_operator_prefix_passthrough(self):
        # '12' is not a mobile operator prefix
        assert apply_bd_mnp("8801206591942") == "8801206591942"

    def test_malformed_number_passthrough_unchanged(self):
        # The converter never mangles a bad number; rejection is a separate
        # policy (is_malformed_bd_mobile), so apply_bd_mnp returns the original.
        assert apply_bd_mnp("880175440173") == "880175440173"

    def test_empty_passthrough(self):
        assert apply_bd_mnp("") == ""


class TestIsMalformedBdMobile:
    def test_short_bd_mobile_is_malformed(self):
        # 12 digits (one short): national '175440173' (9), op '17' is a mobile.
        assert is_malformed_bd_mobile("880175440173") is True

    def test_long_bd_mobile_is_malformed(self):
        # 14 digits (one long): national '18140614639' (11), op '18' is a mobile.
        assert is_malformed_bd_mobile("88018140614639") is True

    def test_national_format_short_mobile_is_malformed(self):
        # 0175440173 -> national '175440173' (9), op '17' is a mobile.
        assert is_malformed_bd_mobile("0175440173") is True

    def test_valid_mobile_is_not_malformed(self):
        assert is_malformed_bd_mobile("8801714101351") is False

    def test_dash_separated_valid_is_not_malformed(self):
        # Separators are stripped before the length check.
        assert is_malformed_bd_mobile("8801730-032367") is False

    def test_non_mobile_operator_is_not_malformed(self):
        # '12' prefix is not a mobile attempt -> must not be rejected.
        assert is_malformed_bd_mobile("8801206591942") is False

    def test_international_non_bd_is_not_malformed(self):
        # An India number must never be rejected as 'malformed BD'.
        assert is_malformed_bd_mobile("919876543210") is False

    def test_empty_is_not_malformed(self):
        assert is_malformed_bd_mobile("") is False

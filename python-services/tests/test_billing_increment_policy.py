"""Billing-increment policy: charge billsec under an X/Y rule, with NO +1 round-up.

Billing is computed on ``billsec`` (answered talk time). The carrier's per-call
``+1`` second seen in the MMCL CDR is the CARRIER's own ceil rounding and is NOT
applied by our rater. Our charge instead follows the rate's configured X/Y
increment policy:

    X = min_duration      (minimum billable seconds / first interval)
    Y = billing_increment (block size subsequent seconds round UP to)

Standard policies: 1/1 -> (min=1, inc=1); 30/6 -> (30, 6); 60/60 -> (60, 60).
This pins ``billable_seconds`` — the single function ``calculate_cost`` uses to
turn billsec into chargeable seconds — so the policy can't silently regress.
"""

from billing.cost_basis import billable_seconds


# ── per-second (1/1): charge exactly billsec, never billsec+1 ──────────────
def test_one_one_charges_exactly_billsec_no_plus_one():
    assert billable_seconds(43, min_duration=1, billing_increment=1) == 43  # NOT 44
    assert billable_seconds(72, min_duration=1, billing_increment=1) == 72
    assert billable_seconds(1, min_duration=1, billing_increment=1) == 1


def test_one_one_applies_one_second_minimum():
    # A 0s answered edge still bills the 1s minimum under 1/1.
    assert billable_seconds(0, min_duration=1, billing_increment=1) == 1


# ── 30/6: 30s minimum, then 6s blocks ─────────────────────────────────────
def test_thirty_six_minimum_and_blocks():
    assert billable_seconds(5, min_duration=30, billing_increment=6) == 30   # below min
    assert billable_seconds(30, min_duration=30, billing_increment=6) == 30  # exact min
    assert billable_seconds(31, min_duration=30, billing_increment=6) == 36  # +1 block
    assert billable_seconds(33, min_duration=30, billing_increment=6) == 36
    assert billable_seconds(36, min_duration=30, billing_increment=6) == 36  # exact block
    assert billable_seconds(37, min_duration=30, billing_increment=6) == 42  # next block


# ── 60/60: per-minute ─────────────────────────────────────────────────────
def test_sixty_sixty_per_minute():
    assert billable_seconds(5, min_duration=60, billing_increment=60) == 60
    assert billable_seconds(60, min_duration=60, billing_increment=60) == 60
    assert billable_seconds(61, min_duration=60, billing_increment=60) == 120
    assert billable_seconds(120, min_duration=60, billing_increment=60) == 120
    assert billable_seconds(121, min_duration=60, billing_increment=60) == 180


# ── defaults degrade safely (matches calculate_cost: inc<1 -> 1, min -> 0) ─
def test_zero_or_missing_increment_defaults_to_per_second():
    assert billable_seconds(43, min_duration=0, billing_increment=0) == 43
    assert billable_seconds(43) == 43

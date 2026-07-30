"""Trunk (carrier) cost basis: talk time (billsec), plus the carrier's round-up.

Two separate findings are encoded here, in the order they were discovered.

1. **Basis is billsec, not duration.** Commit e410975 computed ``trunk_cost``
   from ``cdr.duration`` (the full ring+talk window) on the assumption that the
   carrier bills ring time. The MMCL-001 carrier CDR for 2026-06-20 disproves
   this: every carrier ``BILLABLE_DURATION`` equals our ``billsec`` (within 1s of
   per-second rounding) and runs ~14s/call BELOW ``duration``. Fixed in bc6a5bb.

2. **The carrier rounds UP; Asterisk truncates DOWN.** Commit e4583ed added a
   +1s correction for answered calls. Reconciliation against the MMCL carrier CDR
   for 2026-07-01..07 (45,954 calls matched on A-party CLI + B-party MNP number)
   found the carrier billed exactly ``billsec + 1`` on 89.00% of calls and exactly
   ``billsec`` on 9.90%. Without the correction we under-accrued 0.605% across the
   week; with it we over-accrue 0.168% — a 3.6x accuracy improvement that errs on
   the safe side for a payable.

These tests originally asserted the pre-e4583ed behaviour and were never updated
when the +1 landed, so they sat red while asserting the opposite of the deployed,
evidence-backed intent. That is worse than no test: the obvious way to make them
pass is to delete the +1 and silently reintroduce a 0.6% under-accrual on every
carrier invoice.

The +1 is safe only because both rate groups use ``billing_increment = 1``
(verified 2026-07-21). Under a 30/6 or 60/60 policy it could tip a call into a
whole extra increment — revisit these tests if the increment policy changes.
"""

from billing.cost_basis import trunk_billable_seconds


def test_trunk_cost_billed_on_billsec_not_duration():
    # Real call from the carrier reconciliation: billsec=43, duration=50.
    # Basis is billsec (43), not duration (50); +1 is the carrier's round-up.
    assert trunk_billable_seconds(billsec=43, duration=50) == 44


def test_trunk_basis_excludes_ring_time():
    # As ring/PDD grows, duration grows but the carrier-billed seconds must not.
    # 160s duration must not leak into the basis: 100 talk + 1 round-up.
    assert trunk_billable_seconds(billsec=100, duration=160) == 101


def test_trunk_basis_matches_when_no_ring():
    # No ring time → billsec == duration → basis is unambiguous.
    assert trunk_billable_seconds(billsec=72, duration=72) == 73


def test_ring_time_never_enters_the_basis():
    """The whole point of finding 1: duration must not influence the result."""
    assert trunk_billable_seconds(billsec=30, duration=30) \
        == trunk_billable_seconds(billsec=30, duration=300)


def test_unanswered_calls_never_accrue_trunk_cost():
    """0 must stay 0 -- the +1 applies only to answered calls.

    If the round-up were applied unconditionally, every one of the ~74% of calls
    that never answer would accrue a second of carrier cost.
    """
    assert trunk_billable_seconds(billsec=0, duration=0) == 0
    assert trunk_billable_seconds(billsec=0, duration=45) == 0

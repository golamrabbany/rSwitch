"""Trunk (carrier) cost is billed on talk time (billsec), not ring+talk (duration).

Root cause of a prior margin overstatement: commit e410975 computed
``trunk_cost`` from ``cdr.duration`` (the full ring+talk window) on the
assumption that the carrier bills ring time. The MMCL-001 carrier CDR for
2026-06-20 disproves this: every carrier ``BILLABLE_DURATION`` equals our
``billsec`` (within 1s of per-second rounding) and runs ~14s/call BELOW
``duration`` — i.e. the carrier does NOT bill ring time. Trunk cost must
therefore use ``billsec``, matching the client/reseller basis so margins
reconcile on a single, consistent measurement.
"""

from billing.cost_basis import trunk_billable_seconds


def test_trunk_cost_billed_on_billsec_not_duration():
    # Real call from the carrier reconciliation: billsec=43, duration=50.
    assert trunk_billable_seconds(billsec=43, duration=50) == 43


def test_trunk_basis_excludes_ring_time():
    # As ring/PDD grows, duration grows but the carrier-billed seconds must not.
    assert trunk_billable_seconds(billsec=100, duration=160) == 100


def test_trunk_basis_matches_when_no_ring():
    # No ring time → billsec == duration → basis is unambiguous.
    assert trunk_billable_seconds(billsec=72, duration=72) == 72

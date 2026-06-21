"""Which call-duration field each cost is billed on.

The carrier/trunk provider invoices on answered talk-time (``billsec``), NOT
the full ring+talk window (``duration``). Verified against the MMCL-001 carrier
CDR for 2026-06-20: carrier ``BILLABLE_DURATION`` == our ``billsec`` (within 1s
of per-second rounding) and runs ~14s/call BELOW ``duration`` — ring time is not
billed. Keeping trunk cost on ``billsec`` matches the client (sell) and reseller
(cost) basis so margins reconcile on a single measurement.

This replaces the ``cdr.duration`` basis introduced in commit e410975, which
assumed the carrier billed ring+talk and overstated trunk cost (~12% on
ring-heavy traffic).
"""


def trunk_billable_seconds(billsec: int, duration: int) -> int:
    """Return the seconds the carrier bills the trunk cost on.

    ``duration`` is accepted (not used) so call sites read
    ``trunk_billable_seconds(cdr.billsec, cdr.duration)`` and make the choice
    between the two candidate fields explicit at the point of use.
    """
    return billsec

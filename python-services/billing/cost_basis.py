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

import math


def billable_seconds(billsec: int, min_duration: int = 0, billing_increment: int = 1) -> int:
    """Chargeable seconds for ``billsec`` under an X/Y increment policy.

    ``min_duration`` (X) is the minimum billable seconds (first interval);
    ``billing_increment`` (Y) is the block size subsequent seconds round UP to.
    Standard telecom policies: 1/1 -> (1, 1); 30/6 -> (30, 6); 60/60 -> (60, 60).

    Input is ``billsec`` (talk time) — there is NO carrier-style ``+1`` round-up
    here. This is the single source of truth used by
    ``RatingService.calculate_cost`` (kept identical to its prior inline math:
    increment floored at 1, minimum floored at 0).
    """
    increment = max(1, int(billing_increment or 1))
    minimum = max(0, int(min_duration or 0))
    effective = max(int(billsec), minimum)
    return math.ceil(effective / increment) * increment


def trunk_billable_seconds(billsec: int, duration: int) -> int:
    """Return the seconds the carrier bills the trunk cost on.

    ``duration`` is accepted (not used) so call sites read
    ``trunk_billable_seconds(cdr.billsec, cdr.duration)`` and make the choice
    between the two candidate fields explicit at the point of use.
    """
    return billsec

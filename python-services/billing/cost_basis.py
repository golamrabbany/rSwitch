"""Which call-duration field each cost is billed on.

The carrier/trunk provider invoices on answered talk-time (``billsec``), NOT
the full ring+talk window (``duration``). Verified against the MMCL-001 carrier
CDR for 2026-06-20: carrier ``BILLABLE_DURATION`` == our ``billsec`` (within 1s
of per-second rounding) and runs ~14s/call BELOW ``duration`` — ring time is not
billed. Keeping trunk cost on ``billsec`` matches the client (sell) and reseller
(cost) basis so margins reconcile on a single measurement.

That parenthetical "within 1s of per-second rounding" turned out to be the whole
story: a full week of carrier CDR (2026-07-01..07, 45,954 calls matched) showed
the carrier rounds UP and we truncate DOWN, so it is a *systematic* +1s on 89%
of calls — 0.605% under-accrual, not measurement noise. ``trunk_billable_seconds``
now corrects for it; see its docstring.

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
    here, deliberately: this function prices what we SELL (client) and what the
    reseller is charged, and rounding those up is a pricing change, not a
    correctness fix. Only ``trunk_billable_seconds`` — what we BUY — carries the
    carrier's round-up. This is the single source of truth used by
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

    **The +1 is a rounding correction, not a fudge.** The carrier rounds each
    call UP to the next whole second, while Asterisk's ``billsec`` is an integer
    that has already been truncated DOWN — so for any call with fractional talk
    time the carrier bills exactly one second more than we accrue, and the
    fraction needed to compute the true ceiling is gone by the time we see it.

    Measured against the MMCL carrier CDR for 2026-07-01..07 (45,954 calls
    matched on A-party CLI + B-party MNP number):

      * carrier billed exactly ``billsec + 1`` on 89.00% of calls
      * carrier billed exactly ``billsec``     on  9.90% (whole-second talk)
      * we under-accrued 35,942s = 599 min = **0.605%** across the week

    Adding 1s leaves us over-accruing ~10,012s = 167 min = 0.168% — a 3.6x
    accuracy improvement that errs on the safe side for a payable, which is the
    right direction to be wrong about money we owe.

    Only answered calls are adjusted; 0 stays 0 so unanswered calls never
    accrue cost. Safe because both rate groups use ``billing_increment = 1``
    (verified 2026-07-21) — under a 30/6 or 60/60 policy this +1 could tip a
    call into a whole extra increment and would need revisiting.

    NOTE: this deliberately changes TRUNK cost only. Client and reseller
    charges stay on raw ``billsec`` via ``billable_seconds()`` — moving those
    to a round-up basis would raise customer bills and is a pricing decision,
    not a correctness fix.
    """
    talk = int(billsec)
    if talk <= 0:
        return 0
    return talk + 1

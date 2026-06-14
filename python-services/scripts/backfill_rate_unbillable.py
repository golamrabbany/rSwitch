#!/usr/bin/env python3
"""
Backfill re-rate + charge for ANSWERED calls stuck at status='unbillable'.

Why this exists
---------------
On a BD deploy where rate tables hold international 880-prefixed rows but the
engine stored callee in national format (01XXXXXXXXX), longest-prefix match
failed and ~99% of answered calls became `unbillable` (real billsec, but
billable_duration=0 and cost=0). After fixing real-time rating
(billing.number_format.normalize_bd_msisdn), this script re-rates and charges
the historical backlog so reports/ACD and balances are correct.

Safety / correctness
--------------------
- Uses the PYTHON path (rate_call + charge_call), NOT the PHP command:
  charge_call is idempotent (skips if status=='charged'), charges client +
  reseller, and on insufficient balance does NOT debit (no negative balances).
  Using the PHP rater would leave calls at 'rated' and the celery rate_batch
  stuck-'rated' sweep would then re-charge them -> double charge.
- Only touches status='unbillable', disposition='ANSWERED', billsec>0 in the
  window, so it never re-processes already-charged calls.
- Watch the reseller-blocked Redis flag: charging reseller_cost retroactively
  can block a low-balance reseller and reject its clients' live calls.

Usage
-----
    PYTHONPATH=/var/www/rswitch/python-services \
        venv/bin/python scripts/backfill_rate_unbillable.py [DATE_FROM] [DATE_TO]

DATE_FROM / DATE_TO are inclusive-start / exclusive-end dates (YYYY-MM-DD).
Defaults to the last 7 days if omitted.
"""
import sys
import time
from datetime import date, timedelta

import redis as redis_lib
from sqlalchemy import text

from shared.config import get_settings
from shared.database import get_session
from billing.rating import RatingService
from billing.balance import BalanceService


def main() -> int:
    date_from = sys.argv[1] if len(sys.argv) > 1 else str(date.today() - timedelta(days=7))
    date_to = sys.argv[2] if len(sys.argv) > 2 else str(date.today() + timedelta(days=1))

    settings = get_settings()
    redis_client = redis_lib.from_url(getattr(settings, "redis_url", "redis://127.0.0.1:6379/0"))
    rating = RatingService(redis_client)
    balance = BalanceService()

    with get_session() as session:
        ids = [
            r[0]
            for r in session.execute(
                text(
                    "SELECT id FROM call_records "
                    "WHERE disposition='ANSWERED' AND status='unbillable' AND billsec>0 "
                    "AND call_start >= :df AND call_start < :dt "
                    "ORDER BY id"
                ),
                {"df": date_from, "dt": date_to},
            ).fetchall()
        ]

    print(f"backfill start: {len(ids)} unbillable calls [{date_from} .. {date_to})", flush=True)
    rated = charged = unbillable = ins_client = ins_reseller = failed = 0
    t0 = time.time()
    for i, cdr_id in enumerate(ids, 1):
        try:
            result = rating.rate_call(cdr_id)
            if result.get("status") == "rated":
                rated += 1
                charge = balance.charge_call(cdr_id)
                charged += 1
                if charge.get("client_charged") is False:
                    ins_client += 1
                if charge.get("reseller_charged") is False:
                    ins_reseller += 1
            else:
                unbillable += 1
        except Exception as e:  # noqa: BLE001 — one bad CDR must not abort the backfill
            failed += 1
            if failed <= 15:
                print(f"  fail cdr={cdr_id}: {e}", flush=True)
        if i % 2000 == 0:
            rate = i / (time.time() - t0)
            print(
                f"  {i}/{len(ids)} rated={rated} charged={charged} unbillable={unbillable} "
                f"ins_client={ins_client} ins_reseller={ins_reseller} failed={failed} ({rate:.0f}/s)",
                flush=True,
            )

    print(
        f"DONE rated={rated} charged={charged} unbillable={unbillable} "
        f"ins_client={ins_client} ins_reseller={ins_reseller} failed={failed} "
        f"elapsed={time.time() - t0:.0f}s",
        flush=True,
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

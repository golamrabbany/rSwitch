# QoS Analysis Report — Design Spec

**Date:** 2026-07-30
**Status:** Approved (design), pending implementation
**Author:** Claude + Md Golam Rabbany
**Builds on:** `2026-07-29-rtp-qos-capture-design.md` (the capture this report reads)

## 1. Overview

Surface the per-leg RTP quality now being captured on every outbound CDR as a
**Call Quality** page in the admin Operational Reports, answering two questions
from one screen:

1. **Is the carrier degrading, and when?** — trend over time, for disputes with
   MMCL-001.
2. **When a client reports bad audio, whose fault is it?** — the customer-leg vs
   carrier-leg split attributes the fault to their network or ours.

Today the captured data is only reachable through the three filter tabs on the
Outbound Calls report (no-audio / one-way / high-loss), which find individual bad
calls but cannot show a rate, a trend, or a ranking.

### Evidence this is worth building (2h sample, 2026-07-30)

| Metric | Carrier leg | Customer leg |
|---|---|---|
| Avg jitter | **14.23 ms** | 4.85 ms |
| Loss | 0.003% | 0.008% |
| Avg RTT | 54.18 ms | — |

Carrier-side jitter running ~3x the customer side is exactly the kind of standing
asymmetry that is invisible per-call and obvious in aggregate.

## 2. Requirements

| # | Requirement |
|---|-------------|
| R1 | Capture Asterisk's own Media Experience Score per leg (`rxmes`), already present in the rtcp summary and currently discarded. |
| R2 | A **Call Quality** page under Operational Reports with a date-range filter matching the other report pages. |
| R3 | Summary cards: MES per leg, avg jitter per leg, loss %, avg RTT, and counts of no-audio / one-way calls in the range. |
| R4 | A quality-over-time chart, hourly buckets, plotting carrier vs customer jitter as two series. |
| R5 | A breakdown table pivotable by **Client / Trunk / Destination prefix**, sorted worst-first. |
| R6 | **Capture coverage** shown explicitly — what share of answered calls in the range actually have QoS data. |
| R7 | Multi-tenant scoping enforced: non-super-admins see only their own hierarchy. |
| R8 | `rtp_cust_tx_count` must not be surfaced anywhere on this page (known-unreliable). |

## 3. Data extension

Two additive columns on `call_records`, same pattern as the twelve before them:

| Column | Type | Meaning |
|---|---|---|
| `rtp_cust_rx_mes` | `DECIMAL(5,2) NULL` | Media Experience Score of audio received from the customer phone |
| `rtp_trunk_rx_mes` | `DECIMAL(5,2) NULL` | Media Experience Score of audio received from the carrier |

Only the **rx** score per leg. `txmes` is our own send-side score and answers no
question this page asks; omitting it keeps the table narrower.

**No dialplan change.** `CHANNEL(rtcp,all,audio)` already returns
`rxmes=83.194000;txmes=82.967221` in the summary the engine reads every call — the
parser simply discards them today. The change is:

- `rtp_qos.py`: 1 entry in `_FIELD_MAP` (`rx_mes` -> `rxmes`), parsed with the
  existing `parse_seconds`. **Note the name is misleading here**: `parse_seconds`
  is really "parse a non-negative decimal, rejecting NaN and infinity", which is
  exactly what a 0-100 MES needs. Reuse it rather than writing a second parser —
  its NaN/infinity guards were added for a real production crash and must not be
  re-derived. The implementation plan should widen its docstring to say so.
- `call_end_handler.py`: 1 extra column + bind in the existing UPDATE.
- `leg_qos_handler.py`: 1 extra column + bind.
- `CallRecord::casts()`: 2 entries as `decimal:2`.

`RTP_COLUMN_SUFFIXES` gains `rx_mes`, so both handlers pick it up through the same
dict they already consume.

**NULL vs 0 still applies**: absent -> `NULL`, and a genuine `0` score means
measured-and-terrible. Never coalesce.

### Score presentation

Asterisk's MES is **0-100**, not the familiar 1-5 MOS. Display the measured value
as-is, labelled MES, with a plain band:

| MES | Band |
|---|---|
| >= 80 | Excellent |
| 60-79 | Good |
| 40-59 | Fair |
| < 40 | Poor |

Do **not** rescale to 1-5. Inventing a conversion would reintroduce exactly the
"our formula, our bug" risk that choosing the measured score avoided.

## 4. The page

- Route: `operational-reports/quality` -> `OperationalReportController::qualityReport()`
- View: `resources/views/admin/operational-reports/quality.blade.php`
- Nav: `resources/views/layouts/admin.blade.php`, inserted after **Outbound Calls**

### 4.1 Summary cards
Eight cards, every leg-specific metric named by leg so none is ambiguous:
Carrier MES, Customer MES, carrier jitter, customer jitter, **carrier loss %**,
**customer loss %**, avg RTT (carrier), and a combined no-audio / one-way count.
Existing horizontal stat-card style (icon left).

### 4.2 Quality over time
Chart.js line chart, hourly buckets, two series (carrier jitter, customer jitter).
Follows the existing `initDailyChart`/`initHourlyChart` pattern already in
`resources/views/admin/dashboard.blade.php`, which loads Chart.js 4.4.1 by CDN —
reuse that, do not add a bundled dependency.

### 4.3 Breakdown table
Pivot via a `group_by` query param accepting `client`, `trunk`, `prefix`
(default `client`). Columns: name, calls, answered, avg carrier MES, avg carrier
jitter, carrier loss %, avg RTT, no-audio count. Ordered worst-first by carrier
MES ascending, NULLs last.

## 5. Queries and performance

- **Every query filters on `call_start`** so daily partition pruning works. This
  is the discipline that took the CDR page from 4,421 ms to 14 ms.
- Range is capped at **31 days**, mirroring `hourlySummary()`, so an unbounded
  range cannot scan every partition.
- Aggregates read `call_records` directly, **not** `cdr_summary_hourly` — that
  rollup has no QoS columns. Extending the rollup is a sensible later
  optimisation and is explicitly out of scope here.
- NULL rows are excluded from averages by SQL's own semantics; counts of
  captured rows are reported separately (R6) so an average over 280 rows is never
  read as covering 1,033.
- Loss percentage is `SUM(loss) / NULLIF(SUM(count), 0)` — aggregate ratio, not an
  average of per-call ratios, and divide-by-zero guarded.

## 6. Authorization

Follows the established pattern in this controller:

```php
if (! $authUser->isSuperAdmin()) {
    $query->whereIn('user_id', $authUser->descendantIds());
}
```

plus optional `reseller_id` / `client_id` filters. A reseller must never see
another reseller's call quality.

## 7. Scope

- **Outbound (`sip_to_trunk`) only** — that is where capture happens. Inbound has
  no DIDs and no traffic.
- No export endpoint in this change. (Note: the existing outbound export already
  ignores its `audio` filter — a known separate defect, not fixed here.)
- No alerting or thresholds-based notification. Read-only reporting.
- `cdr_summary_hourly` is not extended.

## 8. Risks

| Risk | Mitigation |
|---|---|
| Migration on a partitioned table | Additive `ADD COLUMN` only; the identical 12-column migration took 44s across 95 partitions with zero call impact. Run off-peak. |
| Engine restart to load the parser change | Measured 4x this session at ~1-2s, established calls unaffected. Only new call attempts inside the window fail (~1.2/s). |
| Averages over thin data misleading the reader | R6 capture-coverage figure is mandatory, not decorative. |
| Someone trusting `rtp_cust_tx_count` | R8: it is not surfaced, and the page carries a note. |
| Long date range scanning many partitions | 31-day cap + `call_start` predicate on every query. |

## 9. Testing

1. **Parser** — `rx_mes` parsed from a real summary string; absent -> `None`;
   `rxmes=0` -> `0` not `None`.
2. **Handlers** — both UPDATEs name the new column; `leg_qos` still writes no
   billing column.
3. **Controller** — the source-assertion technique already used for the audio
   filters: every aggregate query carries a `call_start` bound, and the
   non-super-admin scoping branch is present.
4. **Live** — after deploy, confirm `rtp_trunk_rx_mes` populates on answered
   calls and the page renders for a super-admin and for a reseller, with the
   reseller seeing only their own rows.

## 10. Success criteria

- MES populates on >=95% of newly answered outbound calls, matching the existing
  capture rate.
- The Call Quality page renders for a date range and shows a non-zero capture
  coverage figure.
- The breakdown table ranks worst-first and its numbers reconcile with the same
  aggregate computed directly in SQL.
- A reseller account sees only its own clients' calls.
- No billing change: the `leg_qos` write path still touches only `rtp_trunk_*`.

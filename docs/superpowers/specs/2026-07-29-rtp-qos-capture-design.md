# Per-Leg RTP QoS Capture — Design Spec

**Date:** 2026-07-29
**Status:** Approved (design), pending implementation
**Author:** Claude + Md Golam Rabbany

## 1. Overview

Capture per-leg RTP quality statistics for every outbound call and store them on
the CDR, so that **no-audio** and **one-way-audio** calls become detectable after
the fact instead of being invisible.

Today a call that answered but carried no audio is indistinguishable from a
normal short call: `call_records` has no RTP or QoS columns at all, and both look
like `billsec > 0, hangup_cause 16`. Asterisk already computes the numbers per
channel — they are simply discarded at hangup.

"Both legs" means the two independent RTP streams either side of Asterisk:

```
customer phone  <--RTP(cust)-->  Asterisk  <--RTP(trunk)-->  MMCL-001 carrier
```

These fail separately and have different owners, so a single-leg measurement
would produce a confident number describing the wrong half of the call.

### Motivating evidence (24h, 2026-07-29)

Investigation of "most calls fail below 1 minute" found the short calls were
**normal human hangups**, not audio faults: 99.2% ended with cause 16, only 3.4%
of answered calls died under 5s, and 1,078 calls ran past 5 minutes. Live RTP
sampling showed 29 of 30 channels at 0% loss — but one channel at **8% transmit
loss**, consistent with the known volatile carrier loss on this trunk. There is
currently **no way to quantify how often that happens**, which is what this
feature provides.

## 2. Requirements

| # | Requirement |
|---|-------------|
| R1 | Record RTP rx/tx packet counts, packet loss, jitter and RTT for **both** the customer leg and the carrier (trunk) leg of every outbound call. |
| R2 | Store **raw counters only** — no derived flags — so detection thresholds live in queries and can change without a migration. |
| R3 | Stats must be keyed to the existing CDR row (`call_records.uuid`) via `CDR_UUID`. |
| R4 | The trunk-leg write path must be **incapable of touching disposition, duration, billsec, cost or rating**. |
| R5 | Failure to capture stats must never affect call handling or billing — degrade silently, log visibly. |
| R6 | Operational Reports gains filters for **No audio / One-way / High loss** on outbound calls. |
| R7 | Outbound calls only (see §7 Scope). |

## 3. Architecture

### 3.1 Where the numbers come from

`CHANNEL(rtpqos,audio,<field>)` — readable **only while the channel exists**, so
capture must happen in a hangup handler on each leg. Fields used: `rxcount`,
`txcount`, `rxploss`, `txploss`, `rxjitter`, `rtt`.

### 3.2 Existing hooks being reused

- `[from-internal]` already pushes `hangup-handler` onto the **customer** leg and
  calls the `call_end` AGI from it.
- The outbound `Dial` already runs a pre-dial subroutine on the **callee (trunk)**
  channel: `Dial(${ROUTE_DIAL_STRING},${ROUTE_DIAL_TIMEOUT},gTb(set-jb^s^1))`.
  `[set-jb]` currently only sets the adaptive jitter buffer.

Both hooks exist; neither currently captures QoS.

### 3.3 Data flow

```
customer leg hangs up
  -> [hangup-handler]  Set(RTP_CUST_* = CHANNEL(rtpqos,...))
  -> AGI /call_end     (existing)  -- writes disposition, duration, billsec
                                      AND the cust_* columns in the same UPDATE

trunk leg hangs up
  -> [set-jb] pushed   [leg-qos] handler at pre-dial time
  -> [leg-qos]         Set(RTP_TRUNK_* = CHANNEL(rtpqos,...))
  -> AGI /leg_qos      (NEW)      -- writes ONLY the trunk_* columns
```

The customer leg needs **no extra AGI round-trip** — its values ride along on the
`call_end` call that already happens. Only the trunk leg adds a call.

### 3.4 `CDR_UUID` inheritance (required change)

`outbound_handler.py` sets `CDR_UUID` as a plain channel variable, which child
channels do **not** inherit. The trunk leg therefore has no idea which CDR row it
belongs to. Fixed by setting an inheritable copy before each trunk `Dial`:

```
same => n,Set(__CDR_UUID=${CDR_UUID})
```

The double underscore makes it inherit to all descendant channels; the child sees
it as plain `CDR_UUID`. This is additive — the caller leg keeps its own
`CDR_UUID` and `[hangup-handler]` is unaffected.

## 4. Schema

12 additive columns on `call_records` (partitioned; `ADD COLUMN` only, matching
how `origin_sip_account_id` was previously added).

| Column | Type | Meaning |
|---|---|---|
| `rtp_cust_rx_count` | `INT UNSIGNED NULL` | packets received from the customer phone |
| `rtp_cust_tx_count` | `INT UNSIGNED NULL` | packets sent to the customer phone |
| `rtp_cust_rx_loss` | `INT UNSIGNED NULL` | packets lost inbound |
| `rtp_cust_tx_loss` | `INT UNSIGNED NULL` | packets lost outbound |
| `rtp_cust_rx_jitter` | `DECIMAL(8,3) NULL` | inbound jitter, seconds |
| `rtp_cust_rtt` | `DECIMAL(8,3) NULL` | round-trip time, seconds |
| `rtp_trunk_rx_count` | `INT UNSIGNED NULL` | packets received from the carrier |
| `rtp_trunk_tx_count` | `INT UNSIGNED NULL` | packets sent to the carrier |
| `rtp_trunk_rx_loss` | `INT UNSIGNED NULL` | packets lost inbound |
| `rtp_trunk_tx_loss` | `INT UNSIGNED NULL` | packets lost outbound |
| `rtp_trunk_rx_jitter` | `DECIMAL(8,3) NULL` | inbound jitter, seconds |
| `rtp_trunk_rtt` | `DECIMAL(8,3) NULL` | round-trip time, seconds |

All `NULL`-able: NULL means "not captured" (pre-migration rows, or a leg that
never came up), which is deliberately distinct from `0` meaning "no packets".
That distinction is the whole point — `0` is the no-audio signal.

Storage cost ≈ 100 MB/year at ~28k calls/day. Negligible.

## 5. Detection rules (query-time, not stored)

At the standard 20 ms packetisation, a healthy call carries **50 packets/second**,
so expected packets ≈ `billsec × 50`.

| Condition | Rule |
|---|---|
| **No audio** | `billsec > 0 AND rtp_trunk_rx_count = 0` |
| **One-way** | one direction ≈ 0 while the opposite direction is healthy |
| **Audio died mid-call** | `rx_count < billsec * 25` (under 50% of expected) |
| **High loss** | `rx_loss / NULLIF(rx_count,0) > 0.05` |
| **Choppy** | `rx_jitter > 0.030` (30 ms) |

Each rule is evaluated **per leg** — apply it to the `rtp_cust_*` columns and to
the `rtp_trunk_*` columns independently. A rule written without a leg prefix above
is shorthand for "either leg". Rows where the relevant column is `NULL` are
excluded from every rule, since NULL means "not captured", not "zero packets".

Comparing the `cust_` and `trunk_` sides of the same row is what attributes a
fault to the customer's network versus the carrier.

## 6. Components to change

| Component | Change |
|---|---|
| `database/migrations/` | New migration adding the 12 columns. |
| `/etc/asterisk/extensions.conf` | `Set(__CDR_UUID=...)` before both trunk Dials; `[set-jb]` pushes the `leg-qos` handler; new `[leg-qos]` context; `[hangup-handler]` sets `RTP_CUST_*`. |
| `python-services/call_control/call_end_handler.py` | Read `RTP_CUST_*`, include in the existing `UPDATE`. |
| `python-services/call_control/leg_qos_handler.py` | **New.** Reads `CDR_UUID` + `RTP_TRUNK_*`, updates only trunk columns. |
| `python-services/call_control/agi_server.py` | Instantiate the handler alongside `_outbound`/`_call_end`, and add `elif script == "leg_qos": await _leg_qos.handle(conn, session)` to the dispatch chain (currently lines ~60-71). Unknown scripts already fall through to a `logger.warning`, so a missing registration fails loudly rather than silently. |
| `OperationalReportController::outboundCalls` | No audio / One-way / High loss filters. |
| `resources/views/admin/operational-reports/outbound.blade.php` | Filter tabs + rx/tx columns, following the existing filter-tab pattern. |
| `python-services/tests/` | Tests for the detection thresholds and the parsing of AGI values. |

## 7. Scope

**Outbound (`sip_to_trunk`) only.** In the 24h sample, `sip_to_trunk` was 28,860
calls and `trunk_to_sip` was **zero** — pacevoice has 0 DIDs configured and takes
no inbound traffic. Internal `sip_to_sip` was likewise zero. Instrumenting the
inbound and internal `Dial`s now would be code covering traffic that does not
exist; the pattern extends to them trivially by adding the same `b(set-jb^s^1)`
option and `__CDR_UUID` line if DIDs are enabled later.

## 8. Risks & mitigations

| Risk | Mitigation |
|---|---|
| +1 AGI round-trip per call (~29k/day, ~1.2/s peak) | Engine runs at ~2% of capacity (62 of 3000 calls). Measure AGI latency after deploy; the handler is a single keyed `UPDATE`. |
| AGI unreachable at hangup | Handler runs after the call has ended — losing a stats row must be silent for the caller and logged at `warning` (never `debug`, per the `last_registered_at` lesson). |
| Trunk write path corrupting billing | `leg_qos` updates only the six `rtp_trunk_*` columns. It never touches disposition/duration/billsec/cost, and is a separate handler from `call_end`. |
| `ALTER TABLE` on a partitioned multi-million-row table | Additive `ADD COLUMN` only; same operation already performed for `origin_sip_account_id`. Run off-peak (02:00–05:00 is ~0.09 calls/s). |
| Requires an `rswitch-api` restart | Measured twice on 2026-07-29: ~1–2s, established calls unaffected, 0 orphaned CDRs. Only new call attempts inside the window fail. |
| Dialplan syntax error breaking call routing | `dialplan reload` is validated by `dialplan show from-internal` before and after; keep a backup of `extensions.conf`. |

## 9. Testing

1. **Unit** — detection threshold logic and AGI value parsing (missing/empty
   variables must yield `NULL`, not `0`, since the two mean different things).
2. **Live single call** — place one call and assert both legs populate, with
   `rx_count ≈ billsec × 50` on a healthy call.
3. **Population check** — after deploy, confirm the share of rows with non-NULL
   stats approaches 100% of newly-completed outbound calls.
4. **Negative control** — a call that never answers should have `billsec = 0` and
   near-zero counts, and must not be reported as "no audio".

## 10. Success criteria

- Both legs populated on >95% of newly-completed outbound calls.
- A SQL query returns the count of no-audio calls in the last 24h.
- Operational Reports filters return sane, non-empty results.
- No change to billing: total charged amount over a comparable window is
  unaffected, and no CDR is left `in_progress` by the new handler.

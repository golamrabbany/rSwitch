# rSwitch Development Session Log

**Date**: 2026-02-04
**Repo**: https://github.com/golamrabbany/rSwitch (private)
**Branch**: master

---

## Session Summary

Designed a complete MVP plan for a Softswitch with billing system.

**Tech Stack**: Asterisk 21.x + Laravel 11.x + MySQL 8.x + Redis

**Hierarchy**: Admin → Reseller → Client → SIP Account

---

## Commits (in order)

| Commit | Description |
|---|---|
| `e37e208` | Initial MVP plan — database schema, Asterisk config, Laravel structure, 5 phases |
| `1064015` | Trunks updated to support incoming, outgoing, and both directions + trunk_routes table |
| `34cef23` | Dialplan rewritten with clean unified routing for all 4 call flows |
| `d9b4cd4` | KYC module added for reseller and client verification |
| `7a506eb` | Time-based routing added to trunk_routes (prefix + time window + day of week) |
| `a6b0068` | Payment & recharge system (online self-service, admin manual, reseller-to-client) |
| `af65843` | Session log with full development history |
| `cc5afed` | CDR redesign — single `call_records` table with monthly partitioning for 10M rows/day |
| `e99531a` | Added src_ip and dst_ip to call_records |
| `7f20ed4` | Real-time Redis counters for dashboard stats (replaced 5-min polling) |

---

## What's in the Plan

### Database Tables (15 total + summary tables)
1. `users` — with role, hierarchy, KYC status, balance, billing type
2. `kyc_profiles` — personal/company info, ID details
3. `kyc_documents` — multi-file uploads per KYC profile
4. `sip_accounts` — SIP credentials, linked to Asterisk realtime
5. `trunks` — incoming/outgoing/both with PJSIP config
6. `trunk_routes` — prefix + time-based outgoing trunk selection
7. `dids` — inbound numbers, assigned to clients
8. `rate_groups` — rate plan containers
9. `rates` — prefix-based per-minute rates
10. `call_records` — unified CDR + billing (replaces old cdr + rated_cdr), monthly partitioned
11. `cdr_summary_hourly` — pre-aggregated hourly stats (synced from Redis)
12. `cdr_summary_daily` — pre-aggregated daily stats
13. `cdr_summary_destination` — per-destination daily stats
14. `transactions` — all money movements
15. `payments` — recharge events (online + manual)
16. `invoices` — postpaid monthly invoices
17. `transfer_logs` — audit trail for admin SIP/client transfers
+ Asterisk realtime tables: `ps_endpoints`, `ps_auths`, `ps_aors`, `ps_contacts`

### 4 Call Flows
1. SIP Account → Outbound Trunk (PSTN)
2. SIP Account → SIP Account (same server)
3. Inbound Trunk → SIP Account (DID routing)
4. Inbound Trunk → Outbound Trunk (DID forwarding)

### Key Features
- KYC workflow (submit → pending → approved/rejected)
- Prepaid + postpaid billing
- Per-minute flat rate with longest prefix match
- Time-based trunk routing with day-of-week support
- 3 recharge methods: online (Stripe/PayPal/SSLCommerz), admin manual, reseller-to-client
- Payment history per role
- Real-time billing via hangup handler AGI (no batch CDR processing)
- Redis real-time counters for dashboard stats (sub-ms reads)
- Monthly partitioned call_records for 10M rows/day (12-month retention)
- CDR data lifecycle: hot → warm → cool → cold (archive to CSV/Parquet)
- Admin transfer operations (SIP accounts between clients, clients between resellers)
- Transfer audit logging with metadata snapshots
- 3 AGI handlers: route_outbound, route_inbound, handle_hangup
- Role-based access with Spatie Laravel Permission
- src_ip and dst_ip tracking on all call records

### 5 Implementation Phases
1. **Foundation** — Auth, users, KYC, Asterisk base setup
2. **Core Switching** — SIP accounts, trunks, dialplan, DIDs, CDR
3. **Billing Engine** — Rates, real-time rating via hangup AGI, Redis counters, balance
4. **Business Features** — Reseller workflow, invoicing, auto-suspension, admin transfers
5. **Dashboards & Monitoring** — Per-role dashboards (Redis-powered), live monitor, CDR lifecycle

---

## Key Architecture Decisions (since initial plan)

| Change | Why |
|---|---|
| Merged `cdr` + `rated_cdr` into single `call_records` | 10M rows/day: eliminated double-write (20M→10M inserts), no JOIN needed |
| Monthly RANGE partitioning on `call_start` | Fast queries via partition pruning, easy archival by dropping old partitions |
| Real-time billing via hangup handler AGI | No batch CDR processor needed, billing happens instantly at call end |
| Redis real-time counters (Layer 1) | Dashboards read Redis (sub-ms), not MySQL. Zero DB load for stats |
| MySQL summary sync (Layer 2) | Persistence for invoicing/history, synced from Redis every 5 min |
| Disabled `cdr_adaptive_odbc` | AGI handles all CDR writes directly, avoids duplicate records |

---

## Next Steps
- Start implementing Phase 1 (Foundation)
- Set up Laravel project with auth and role system
- Create all database migrations
- Build KYC module
- Install and configure Asterisk 21 with PJSIP realtime

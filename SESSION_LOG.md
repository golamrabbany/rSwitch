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
| `6f4bb8b` | Admin transfer operations (SIP accounts & clients between owners) |
| `8b9c9c8` | Comprehensive security architecture (Section 7) |

---

## What's in the Plan

### Database Tables (21 total + summary tables)
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
18. `audit_logs` — all admin action audit trail
19. `destination_blacklist` — toll fraud prefix blocking
20. `destination_whitelist` — per-user prefix restriction
21. `rate_imports` — CSV rate import audit trail with error logs
+ Asterisk realtime tables: `ps_endpoints`, `ps_auths`, `ps_aors`, `ps_contacts`, `ps_endpoint_id_ips`

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
- **Security architecture** (Section 7):
  - SIP: Fail2ban, NAT traversal, TLS transport, strong passwords, IP ACLs, module hardening
  - Toll fraud: destination blacklist/whitelist, daily spend limits, concurrent call limits, fraud alerts
  - Web: 2FA (mandatory admin), login rate limiting, account lockout, CSRF, security headers
  - Infra: network segmentation, minimal DB privileges, Redis auth, audit logging
  - AGI: Supervisor auto-restart, AGISTATUS dialplan fallback, health checks
- **Production features** (9 additions from live system review):
  - Rate CSV import/export (upload, preview, validate, MERGE/REPLACE/ADD_ONLY modes)
  - Rate effective dates (schedule future rates, auto-activate)
  - Reseller rate markup model (linked rate groups, minimum margin enforcement)
  - Caller ID / CLI manipulation per trunk (passthrough/override/strip/translate/hide)
  - Dial string manipulation per trunk (pattern match/replace, prefix, strip, tech prefix)
  - SIP account IP authentication (password, IP-based, or both)
  - SIP account IP restriction (allowed_ips per account)
  - Trunk health monitoring (SIP OPTIONS ping, ASR threshold alerts, auto-disable)
  - Minimum balance threshold for calls (min_balance_for_calls per user)

### 5 Implementation Phases
1. **Foundation** — Auth, users, KYC, Asterisk base setup, security hardening (P0)
2. **Core Switching** — SIP accounts (3 auth modes), trunks (CLI + dial manipulation + health monitoring), dialplan, DIDs, CDR
3. **Billing Engine** — Rates (CSV import/export, effective dates), real-time rating, Redis counters, min balance threshold
4. **Business Features** — Reseller markup model, invoicing, auto-suspension, admin transfers
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
| Security section added (Section 7) | Softswitches are high-value fraud targets; multi-layer protection required |
| NAT mandatory on all endpoints | direct_media=no ensures billing accuracy + fixes one-way audio |
| AGI failure fallback (AGISTATUS check) | Prevents silent call drops when AGI daemon is down |
| Toll fraud prevention (blacklist + spend limits) | Blocks premium-rate fraud; auto-suspend on anomaly |
| Rate effective dates + end dates | Schedule future rate changes; old rates auto-expire |
| CSV rate import with 3 modes (MERGE/REPLACE/ADD_ONLY) | Can't manually add 5000+ destination rates |
| Reseller rate markup model (parent_rate_group_id) | Enforces minimum margin; reseller can't sell below admin rate |
| Per-trunk CLI manipulation (5 modes) | Different providers require different CallerID formats |
| Per-trunk dial string manipulation pipeline | Different trunks expect different number formats |
| SIP account 3 auth modes (password/IP/both) | PBX trunking needs IP auth; high-security needs both |
| Trunk SIP OPTIONS health monitoring + ASR alerts | Auto-disable dead trunks; flag degraded ASR |
| Minimum balance threshold per user | Prevents micro-balance abuse; ensures meaningful calls |

---

## Next Steps
- Start implementing Phase 1 (Foundation)
- Set up Laravel project with auth and role system
- Create all database migrations
- Build KYC module
- Install and configure Asterisk 21 with PJSIP realtime

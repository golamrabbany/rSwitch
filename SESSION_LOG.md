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

---

## Session: 2026-02-21 — Add Allow P2P Calls & Allow Call Recording to SIP Accounts

### What Was Done

Added two per-account boolean toggles to SIP accounts:
- **Allow P2P Calls** (default: enabled) — gates internal SIP-to-SIP calls in AGI
- **Allow Call Recording** (default: disabled) — triggers MixMonitor in Asterisk dialplan

### Files Changed

| File | Change |
|---|---|
| `database/migrations/2026_02_21_221148_add_p2p_and_recording_to_sip_accounts_table.php` | New migration: `allow_p2p` (bool, default true), `allow_recording` (bool, default false) |
| `app/Models/SipAccount.php` | Added to `$fillable` and `casts()` |
| `app/Http/Controllers/Admin/SipAccountController.php` | Validation rules in `store()`/`update()`, `$request->boolean()` conversion, optional CSV column handling in `import()` |
| `resources/views/admin/sip-accounts/create.blade.php` | "Call Features" card with Alpine.js toggle switches |
| `resources/views/admin/sip-accounts/edit.blade.php` | Same card, pre-populated from `$sipAccount` |
| `resources/views/admin/sip-accounts/show.blade.php` | P2P and Recording rows with Enabled/Disabled badges |
| `resources/views/admin/sip-accounts/import.blade.php` | Documented `allow_p2p` and `allow_recording` as optional CSV columns |
| `app/Services/Agi/OutboundCallHandler.php` | P2P gate check (rejects with `p2p_disabled`), sets `RECORD_CALL` channel var for external and internal paths |
| `docker/asterisk/conf/extensions.conf` | `ExecIf` MixMonitor before Dial on external and internal paths |
| `installer/templates/asterisk/extensions.conf.template` | Same MixMonitor changes |
| `installer/update.sh` | Copies dialplan template, reloads Asterisk, creates recording directory |
| `CLAUDE.md` | Added Production Server access details |

### Commits

| Commit | Description |
|---|---|
| `a8839aa` | Add Allow P2P Calls & Allow Call Recording toggles to SIP accounts |

### Deployment

Deployed to production server (103.170.231.19 / rswitch.webvoice.net):
- Files uploaded via SCP to `/tmp/` then `sudo cp` into `/var/www/rswitch/`
- Migration ran successfully
- Asterisk dialplan reloaded with MixMonitor support
- Recording directory created at `/var/spool/asterisk/recording/`
- View cache refreshed

### Notes
- Reseller/Client portals do **not** see these toggles — DB defaults apply (P2P=on, Recording=off)
- No git repo on production server — deploy by uploading files then `sudo cp`
- Server access saved in CLAUDE.md

---

## Session: 2026-02-23 — P2P Calls Report, SIP Registration Status, Platform Review & Fixes

### What Was Done

#### 1. P2P Calls Report (Operational Reports)
Added a dedicated report page for SIP-to-SIP internal calls under Operational Reports, matching the CDR page layout.

| File | Change |
|---|---|
| `routes/web.php` | Added `operational-reports/p2p` route |
| `app/Http/Controllers/Admin/OperationalReportController.php` | Added `p2pCalls()` method with Carbon date range, filters (user, disposition, search), and stats via `selectRaw` aggregation |
| `resources/views/admin/operational-reports/p2p.blade.php` | Created — CDR-style layout with `cdr-stats-grid`, `filter-card`, `data-table` classes, 4-column stats grid, user auto-suggest filter (Alpine.js), and CDR detail links |
| `resources/views/layouts/admin.blade.php` | Added "P2P Calls" sidebar nav link |

#### 2. SIP Account Registration Status
Added live registration status with source IP to the SIP accounts list, querying Asterisk CLI directly.

| File | Change |
|---|---|
| `app/Http/Controllers/Admin/SipAccountController.php` | Added `shell_exec('sudo asterisk -rx "pjsip show contacts"')` to parse live registrations, replaces old `last_registered_at` display |
| `resources/views/admin/sip-accounts/index.blade.php` | Shows "Registered (IP)" in green or "Unregistered" in gray under each SIP username |
| `/etc/sudoers.d/asterisk-www-data` | Added on server: `www-data ALL=(ALL) NOPASSWD: /usr/sbin/asterisk` |
| `installer/install.sh` | Added sudoers rule to installer for new deployments |

#### 3. Stale Active Calls Cleanup
Fixed phantom "active calls" showing for calls where the AGI hangup handler didn't fire.

| File | Change |
|---|---|
| `routes/console.php` | Added scheduled cleanup: marks `in_progress` records older than 5 minutes as `completed` or `unbillable` |

#### 4. Critical Platform Review & Fixes
Conducted comprehensive code review and fixed all identified issues.

| # | Issue | Fix | File |
|---|-------|-----|------|
| 1 | **Inbound CDRs broken** — `call_flow = 'inbound'` not in ENUM | Changed to `'trunk_to_sip'` | `InboundCallHandler.php:125` |
| 2 | **Export auth bypass** — any admin could export all SIP accounts | Added `descendantIds()` scoping | `SipAccountController.php:295` |
| 3 | **Password in CSV** — plaintext SIP passwords exported | Removed password from CSV header and data | `SipAccountController.php:329-353` |
| 4 | **Column typo** — `rate_per_min` vs `rate_per_minute` | Fixed to `rate_per_minute` (2 places) | `OutboundCallHandler.php:301,367` |
| 5 | **Balance race condition** — stale balance read on concurrent calls | Added `lockForUpdate()` in `canAffordCall()` | `BalanceService.php:121` |
| 6 | **8+ dashboard queries** — separate COUNT queries per stat | Consolidated to 2 queries with `CASE WHEN` | `OperationalReportController.php:24-55` |
| 7 | **Missing Dec 2026 partition** — jumped from Nov to Jan | Added `p2026_12` in migration + live DB via `REORGANIZE PARTITION` | `create_call_records_table.php:67` |

### Commits

| Commit | Description |
|---|---|
| `2be7633` | Add P2P Calls report under Operational Reports |
| `4c756e7` | Redesign P2P Calls report to match CDR page layout |
| `85de162` | Put P2P filter fields and button in a single row |
| `fe6174d` | Add auto-suggest user filter to P2P Calls report |
| `64c9927` | Fix Duration and Billsec column alignment in P2P Calls |
| `63dfcfa` | Center-align Duration and Billsec columns |
| `3e539c6` | Fix P2P stats grid to 4 columns instead of 5 |
| `f776664` | Show live registration status with source IP in SIP accounts list |
| `b8738f4` | Fix SIP registration: query Asterisk CLI instead of empty ps_contacts table |
| `753439e` | Add scheduled cleanup for stale in_progress call records |
| `105c15a` | Add asterisk sudoers to installer, revert stale cleanup to 5 min |
| `ec8ad42` | Fix critical issues from platform review (7 fixes) |

### Known Long-Term Considerations

| Issue | Status | Notes |
|---|---|---|
| AGI server is single-threaded | **Not yet fixed** | Will block at 10+ concurrent calls. Needs ReactPHP or pcntl_fork |
| No auto partition management | **Not yet fixed** | From 2027 onward, CDRs pile into p_future. Need monthly artisan command |
| SIP passwords stored plaintext | **Accepted** | Required by Asterisk PJSIP realtime. Removed from CSV export |
| PhpMyAdmin in compose.yaml | **Dev only** | Port 8080 — not used on production bare-metal |

### Deployment

All changes deployed to production (103.170.231.19 / rswitch.webvoice.net):
- Files uploaded via SCP, `sudo cp` into `/var/www/rswitch/`
- AGI server restarted via `supervisorctl restart rswitch-agi:*`
- December 2026 partition added to live DB via `ALTER TABLE REORGANIZE PARTITION`
- Sudoers rule added for www-data Asterisk CLI access
- All caches cleared (view, route, application)

## Next Steps
- Implement multi-threaded AGI server (ReactPHP or pcntl_fork) for concurrent call handling
- Add automated monthly partition management command
- Continue Phase 1 implementation

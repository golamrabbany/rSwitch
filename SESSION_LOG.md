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

---

## What's in the Plan

### Database Tables (14 total)
1. `users` — with role, hierarchy, KYC status, balance, billing type
2. `kyc_profiles` — personal/company info, ID details
3. `kyc_documents` — multi-file uploads per KYC profile
4. `sip_accounts` — SIP credentials, linked to Asterisk realtime
5. `trunks` — incoming/outgoing/both with PJSIP config
6. `trunk_routes` — prefix + time-based outgoing trunk selection
7. `dids` — inbound numbers, assigned to clients
8. `rate_groups` — rate plan containers
9. `rates` — prefix-based per-minute rates
10. `cdr` — raw CDR from Asterisk
11. `rated_cdr` — processed/rated CDR with call_flow tracking
12. `transactions` — all money movements
13. `payments` — recharge events (online + manual)
14. `invoices` — postpaid monthly invoices
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
- FastAGI billing gate + CDR processing
- Role-based access with Spatie Laravel Permission

### 5 Implementation Phases
1. **Foundation** — Auth, users, KYC, Asterisk base setup
2. **Core Switching** — SIP accounts, trunks, dialplan, DIDs, CDR
3. **Billing Engine** — Rates, rating, balance, prepaid/postpaid
4. **Business Features** — Reseller workflow, invoicing, auto-suspension
5. **Dashboards & Monitoring** — Per-role dashboards, live monitor, reports

---

## Next Steps
- Start implementing Phase 1 (Foundation)
- Set up Laravel project with auth and role system
- Create all database migrations
- Build KYC module
- Install and configure Asterisk 21 with PJSIP realtime

# Softswitch MVP Plan — Asterisk + Laravel + MySQL

## Tech Stack
- **Asterisk 21.x** (PJSIP, Realtime Architecture)
- **Laravel 11.x** (PHP 8.3+)
- **MySQL 8.x**
- **Redis** (queues, caching, live balance tracking)
- **Supervisor** (queue workers, AGI daemon)

## User Hierarchy
```
Admin → Reseller → Client → SIP Account
```

---

## 1. Database Schema

### Core User & Auth Tables

```sql
-- users (polymorphic role system)
users
├── id (PK)
├── name
├── email (unique)
├── password
├── role ENUM('admin','reseller','client')
├── parent_id (FK→users.id, NULL for admin)
├── status ENUM('active','suspended','disabled')
├── billing_type ENUM('prepaid','postpaid')
├── credit_limit DECIMAL(12,4) DEFAULT 0  -- for postpaid
├── balance DECIMAL(12,4) DEFAULT 0       -- current balance
├── currency VARCHAR(3) DEFAULT 'USD'
├── rate_group_id (FK→rate_groups.id)
├── created_at, updated_at
```

**Hierarchy enforcement**: `parent_id` links Client→Reseller→Admin. Admin has `parent_id = NULL`. Reseller's `parent_id` = Admin. Client's `parent_id` = Reseller.

### SIP Accounts (Asterisk Realtime - PJSIP)

```sql
-- sip_accounts (application table, linked to Asterisk realtime)
sip_accounts
├── id (PK)
├── user_id (FK→users.id, the client who owns this)
├── username VARCHAR(40) UNIQUE     -- SIP username / endpoint name
├── password VARCHAR(80)            -- SIP auth password
├── caller_id_name VARCHAR(80)
├── caller_id_number VARCHAR(20)
├── max_channels INT DEFAULT 2
├── codec_allow VARCHAR(100) DEFAULT 'ulaw,alaw,g729'
├── status ENUM('active','suspended','disabled')
├── last_registered_at TIMESTAMP NULL
├── last_registered_ip VARCHAR(45) NULL
├── created_at, updated_at

-- Asterisk PJSIP Realtime Tables (managed by Asterisk Alembic migrations)
ps_endpoints       -- populated by Laravel when SIP account is created
ps_auths           -- auth credentials
ps_aors            -- address of record
ps_contacts        -- registered contacts (written by Asterisk)
```

### Trunks (Incoming & Outgoing)

A single `trunks` table supports both directions. A trunk can be incoming-only, outgoing-only, or both.

```sql
trunks
├── id (PK)
├── name VARCHAR(100)
├── provider VARCHAR(100)
├── direction ENUM('incoming','outgoing','both')  -- trunk direction
├── host VARCHAR(255)                -- provider SIP server / IP
├── port INT DEFAULT 5060
├── username VARCHAR(100) NULL       -- auth username (for register-based trunks)
├── password VARCHAR(100) NULL       -- auth password
├── register BOOLEAN DEFAULT FALSE   -- send SIP REGISTER to provider
├── register_string VARCHAR(255) NULL -- e.g. user:pass@host/ext for outgoing registration
├── transport ENUM('udp','tcp','tls') DEFAULT 'udp'
├── codec_allow VARCHAR(100) DEFAULT 'ulaw,alaw,g729'
├── max_channels INT DEFAULT 30
├── -- Outgoing-specific fields
├── prefix VARCHAR(10) NULL          -- tech prefix to prepend on outgoing
├── strip_digits INT DEFAULT 0       -- digits to strip from dialed number
├── outgoing_priority INT DEFAULT 1  -- failover ordering for outgoing
├── -- Incoming-specific fields
├── incoming_context VARCHAR(80) DEFAULT 'from-trunk'  -- Asterisk dialplan context
├── incoming_auth_type ENUM('ip','registration','both') DEFAULT 'ip'
├── incoming_ip_acl VARCHAR(255) NULL  -- allowed source IPs (comma-separated CIDRs)
├── -- Common fields
├── status ENUM('active','disabled')
├── notes TEXT NULL
├── created_at, updated_at
├── INDEX idx_direction (direction, status)
├── INDEX idx_outgoing_priority (outgoing_priority)
```

**Direction logic:**
- `incoming` — receives calls from PSTN provider (DIDs routed through this trunk)
- `outgoing` — sends calls to PSTN provider (used for outbound dialing)
- `both` — single trunk handles both directions (common with SIP trunk providers)

**Trunk routing table** for outgoing — maps destination prefixes to specific outgoing trunks:

```sql
trunk_routes
├── id (PK)
├── trunk_id (FK→trunks.id)          -- must be 'outgoing' or 'both' direction
├── prefix VARCHAR(20)               -- destination prefix to match (e.g. '1', '44', '880')
├── priority INT DEFAULT 1           -- lower = higher priority (for failover)
├── weight INT DEFAULT 100           -- load balancing weight among same-priority trunks
├── status ENUM('active','disabled')
├── created_at, updated_at
├── INDEX idx_prefix_priority (prefix, priority)
├── UNIQUE idx_trunk_prefix (trunk_id, prefix)
```

This allows **prefix-based outgoing trunk selection** with failover:
- Call to `1212...` → matches prefix `1` → routes to Trunk A (priority 1), failover to Trunk B (priority 2)
- Call to `44...` → matches prefix `44` → routes to Trunk C
- Multiple trunks with same prefix + priority → load-balanced by weight

### DIDs (Inbound Numbers)

```sql
dids
├── id (PK)
├── number VARCHAR(20) UNIQUE       -- E.164 format
├── provider VARCHAR(100)
├── trunk_id (FK→trunks.id)         -- incoming trunk (must be 'incoming' or 'both')
├── assigned_to_user_id (FK→users.id) NULL  -- client or reseller
├── destination_type ENUM('sip_account','ivr','queue','ring_group','external')
├── destination_id INT NULL         -- FK to relevant table
├── monthly_cost DECIMAL(8,4)       -- admin's cost from provider
├── monthly_price DECIMAL(8,4)      -- price charged to client
├── status ENUM('active','unassigned','disabled')
├── created_at, updated_at
├── INDEX idx_trunk (trunk_id)
```

**Constraint**: `trunk_id` must reference a trunk with `direction = 'incoming'` or `direction = 'both'`.

### Rate Management

```sql
rate_groups
├── id (PK)
├── name VARCHAR(100)
├── description TEXT NULL
├── created_by (FK→users.id)        -- admin or reseller
├── created_at, updated_at

rates
├── id (PK)
├── rate_group_id (FK→rate_groups.id)
├── prefix VARCHAR(20) INDEX        -- destination prefix (e.g. '1', '1212', '44')
├── destination VARCHAR(100)        -- human name (e.g. 'USA', 'UK Mobile')
├── rate_per_minute DECIMAL(10,6)   -- cost per minute
├── connection_fee DECIMAL(10,6) DEFAULT 0
├── min_duration INT DEFAULT 0      -- minimum billable seconds
├── billing_increment INT DEFAULT 6 -- billing block in seconds (6/6, 1/1, etc.)
├── status ENUM('active','disabled')
├── created_at, updated_at
├── INDEX idx_prefix (rate_group_id, prefix)
```

### CDR & Billing

```sql
-- Raw CDR from Asterisk (written by cdr_adaptive_odbc)
cdr
├── id (PK, BIGINT AUTO_INCREMENT)
├── calldate DATETIME
├── clid VARCHAR(80)
├── src VARCHAR(80)
├── dst VARCHAR(80)
├── dcontext VARCHAR(80)
├── channel VARCHAR(80)
├── dstchannel VARCHAR(80)
├── lastapp VARCHAR(80)
├── lastdata VARCHAR(80)
├── duration INT
├── billsec INT
├── disposition VARCHAR(45)
├── amaflags INT
├── accountcode VARCHAR(20)         -- maps to sip_account.username
├── uniqueid VARCHAR(150)
├── userfield VARCHAR(255)
├── INDEX idx_calldate (calldate)
├── INDEX idx_accountcode (accountcode)

-- Processed/rated CDR (created by Laravel billing worker)
rated_cdr
├── id (PK, BIGINT)
├── cdr_id (FK→cdr.id)
├── sip_account_id (FK→sip_accounts.id)
├── user_id (FK→users.id)           -- client
├── reseller_id (FK→users.id) NULL  -- reseller (for commission tracking)
├── direction ENUM('inbound','outbound')
├── caller VARCHAR(40)
├── callee VARCHAR(40)
├── destination VARCHAR(100)        -- matched destination name
├── matched_prefix VARCHAR(20)
├── duration INT                    -- total seconds
├── billable_duration INT           -- after min_duration & increment rounding
├── rate_per_minute DECIMAL(10,6)
├── total_cost DECIMAL(10,4)        -- what it costs client
├── reseller_cost DECIMAL(10,4)     -- what reseller pays admin
├── call_start DATETIME
├── call_end DATETIME
├── hangup_cause VARCHAR(50)
├── processed_at TIMESTAMP
├── INDEX idx_user_date (user_id, call_start)
├── INDEX idx_reseller_date (reseller_id, call_start)

-- Transactions (all money movements)
transactions
├── id (PK)
├── user_id (FK→users.id)
├── type ENUM('topup','call_charge','did_charge','refund','adjustment','invoice_payment')
├── amount DECIMAL(12,4)            -- positive=credit, negative=debit
├── balance_after DECIMAL(12,4)
├── reference_type VARCHAR(50) NULL -- 'rated_cdr', 'invoice', 'manual'
├── reference_id BIGINT NULL
├── description VARCHAR(255)
├── created_by (FK→users.id) NULL
├── created_at
├── INDEX idx_user_date (user_id, created_at)

-- Invoices (postpaid)
invoices
├── id (PK)
├── invoice_number VARCHAR(30) UNIQUE
├── user_id (FK→users.id)
├── period_start DATE
├── period_end DATE
├── call_charges DECIMAL(12,4)
├── did_charges DECIMAL(12,4)
├── total_amount DECIMAL(12,4)
├── tax_amount DECIMAL(12,4) DEFAULT 0
├── status ENUM('draft','issued','paid','overdue','cancelled')
├── due_date DATE
├── paid_at TIMESTAMP NULL
├── created_at, updated_at
```

---

## 2. Asterisk Configuration Architecture

### Integration Model

```
┌─────────────┐     MySQL Realtime     ┌──────────────┐
│   Laravel    │ ──────────────────────>│   Asterisk   │
│  (Web/API)   │   (ps_endpoints,      │    21.x      │
│              │    ps_auths, ps_aors)  │              │
│              │                        │              │
│              │<───── CDR writes ──────│  cdr_adaptive│
│              │                        │  _odbc       │
│              │                        │              │
│  AGI Script  │<───── AGI call ────────│  Dialplan    │
│  (PHP/FastAGI)│                       │              │
│              │                        │              │
│  AMI Client  │<───── Events ──────────│  AMI         │
└─────────────┘                        └──────────────┘
```

### PJSIP Realtime Setup (`pjsip.conf`)

```ini
; /etc/asterisk/pjsip.conf - minimal static config
[global]
type=global

[transport-udp]
type=transport
protocol=udp
bind=0.0.0.0:5060

; SIP account endpoints are loaded dynamically from MySQL via Realtime
; Trunk endpoints are generated by Laravel and written to pjsip_trunks.conf
; via #include or also managed in realtime tables
```

### Trunk PJSIP Config (generated by Laravel → `pjsip_trunks.conf`)

Laravel generates this file dynamically when trunks are added/edited, then reloads PJSIP via AMI.

```ini
; === OUTGOING TRUNK EXAMPLE ===
[trunk-outgoing-1]
type=endpoint
context=from-trunk              ; inbound calls from this trunk land here
transport=transport-udp
disallow=all
allow=ulaw,alaw,g729
outbound_auth=trunk-outgoing-1-auth
aors=trunk-outgoing-1-aor

[trunk-outgoing-1-auth]
type=auth
auth_type=userpass
username=myuser
password=mypass

[trunk-outgoing-1-aor]
type=aor
contact=sip:sip.provider.com:5060

[trunk-outgoing-1-identify]
type=identify
endpoint=trunk-outgoing-1
match=sip.provider.com           ; IP match for incoming from this provider

; === INCOMING-ONLY TRUNK EXAMPLE (IP-authenticated) ===
[trunk-incoming-1]
type=endpoint
context=from-trunk
transport=transport-udp
disallow=all
allow=ulaw,alaw,g729

[trunk-incoming-1-identify]
type=identify
endpoint=trunk-incoming-1
match=203.0.113.10               ; provider's signaling IP

; === REGISTRATION for outgoing trunks that require it ===
[trunk-outgoing-1-reg]
type=registration
outbound_auth=trunk-outgoing-1-auth
server_uri=sip:sip.provider.com
client_uri=sip:myuser@sip.provider.com
retry_interval=60
```

### `extconfig.conf` (Realtime Mapping)

```ini
[settings]
ps_endpoints => odbc,asterisk,ps_endpoints
ps_auths => odbc,asterisk,ps_auths
ps_aors => odbc,asterisk,ps_aors
ps_contacts => odbc,asterisk,ps_contacts
```

### `res_odbc.conf`

```ini
[asterisk]
enabled => yes
dsn => asterisk-connector
username => asterisk_user
password => secret
pre-connect => yes
max_connections => 20
```

### Dialplan (`extensions.conf`)

```ini
; =============================================================
; OUTBOUND CALLS — from SIP accounts to PSTN via outgoing trunks
; =============================================================
[from-internal]
; All registered SIP endpoints land here when they dial
exten => _X.,1,NoOp(Outbound call from ${CALLERID(num)} to ${EXTEN})
 same => n,Set(ACCOUNTCODE=${CHANNEL(endpoint)})
 ; AGI checks balance AND selects best outgoing trunk based on prefix + priority
 same => n,AGI(agi://127.0.0.1:4573,check_balance,${CHANNEL(endpoint)},${EXTEN})
 ; AGI returns: ALLOWED/DENIED, RATE, MAX_DURATION, TRUNK_1, TRUNK_2 (failover)
 same => n,GotoIf($["${AGIRESULT}" = "DENIED"]?denied)
 same => n,Set(CDR(userfield)=${RATE_ID})
 same => n,Set(TIMEOUT(absolute)=${MAX_DURATION})
 ; Prepare dialed number (strip digits + add prefix per trunk config)
 same => n,Set(DIAL_NUM=${TRUNK_PREFIX}${EXTEN:${TRUNK_STRIP}})
 ; Try primary outgoing trunk
 same => n,Dial(PJSIP/${DIAL_NUM}@${TRUNK_1},60,T)
 same => n,GotoIf($["${DIALSTATUS}" = "ANSWER"]?done)
 ; Failover to secondary outgoing trunk if available
 same => n,GotoIf($["${TRUNK_2}" = ""]?notrunk2)
 same => n,Dial(PJSIP/${DIAL_NUM}@${TRUNK_2},60,T)
 same => n(notrunk2),Goto(hangup)
 same => n(denied),Playback(ss-noservice)
 same => n,Hangup()
 same => n(done),Hangup()
 same => n(hangup),Hangup()

; =============================================================
; INBOUND CALLS — from PSTN incoming trunks to SIP accounts/DIDs
; =============================================================
[from-trunk]
; All incoming trunk endpoints use this context
; The AGI looks up which incoming trunk sent the call + DID routing
exten => _X.,1,NoOp(Inbound call on trunk ${CHANNEL(endpoint)} to DID ${EXTEN})
 same => n,Set(ACCOUNTCODE=trunk-${CHANNEL(endpoint)})
 ; AGI looks up DID→destination, validates trunk is incoming/both
 same => n,AGI(agi://127.0.0.1:4573,route_inbound,${CHANNEL(endpoint)},${EXTEN})
 same => n,GotoIf($["${DEST_TYPE}" = "sip_account"]?sip)
 same => n,GotoIf($["${DEST_TYPE}" = "ring_group"]?ringgroup)
 same => n,GotoIf($["${DEST_TYPE}" = "external"]?external)
 same => n,Playback(ss-noservice)
 same => n,Hangup()
 same => n(sip),Dial(PJSIP/${DEST_ENDPOINT},30)
 same => n,Hangup()
 same => n(ringgroup),Dial(${DEST_ENDPOINTS},30)
 same => n,Hangup()
 same => n(external),Dial(PJSIP/${DEST_NUMBER}@${DEST_TRUNK},60)
 same => n,Hangup()

; =============================================================
; INTERNAL SIP-TO-SIP CALLS (between registered endpoints)
; =============================================================
[from-internal-local]
exten => _XXXX,1,NoOp(Internal call to ${EXTEN})
 same => n,Dial(PJSIP/${EXTEN},30)
 same => n,Hangup()
```

### AGI Billing Script (PHP FastAGI)

A PHP FastAGI daemon (using `phpagi` or `PAGI` library) running on port 4573:

```
Key functions:

1. check_balance(endpoint, destination)
   - Look up SIP account → client → rate_group
   - Match destination against rates table (longest prefix match)
   - For prepaid: calculate max_duration = balance / rate_per_minute
   - For postpaid: check credit_limit - balance
   - SELECT OUTGOING TRUNK: query trunk_routes table
     - Match destination prefix against trunk_routes (longest prefix match)
     - Order by priority ASC, pick top 2 for primary + failover
     - Apply trunk's strip_digits and prefix to format the dial string
   - Return: ALLOWED/DENIED, rate, max_duration, trunk_1, trunk_2,
             trunk_prefix, trunk_strip

2. route_inbound(trunk_endpoint, did_number)
   - Identify which incoming trunk the call arrived on (by endpoint name)
   - Validate trunk direction is 'incoming' or 'both'
   - Look up DID → destination mapping from dids table
   - Validate DID belongs to this trunk
   - Return: destination type (sip_account/ring_group/external),
             endpoint(s) to dial, trunk for external forwarding

3. get_trunk_status(trunk_id)
   - Check active channel count vs max_channels for the trunk
   - Used by check_balance to skip trunks that are at capacity
```

### CDR Collection (`cdr_adaptive_odbc.conf`)

```ini
[adaptive_odbc]
connection = asterisk
table = cdr
alias start => calldate
```

---

## 3. Laravel Application Structure

### Directory Layout

```
rSwitch/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── ResellerController.php
│   │   │   │   ├── TrunkController.php       -- incoming, outgoing, both
│   │   │   │   ├── TrunkRouteController.php  -- prefix→trunk routing rules
│   │   │   │   ├── DidController.php
│   │   │   │   ├── RateGroupController.php
│   │   │   │   └── SystemSettingController.php
│   │   │   ├── Reseller/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── ClientController.php
│   │   │   │   ├── SipAccountController.php
│   │   │   │   ├── DidController.php
│   │   │   │   └── RateGroupController.php
│   │   │   ├── Client/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── SipAccountController.php
│   │   │   │   └── CdrController.php
│   │   │   ├── Common/
│   │   │   │   ├── CdrController.php
│   │   │   │   ├── InvoiceController.php
│   │   │   │   ├── TransactionController.php
│   │   │   │   └── ProfileController.php
│   │   │   └── Auth/
│   │   ├── Middleware/
│   │   │   ├── RoleMiddleware.php
│   │   │   └── TenantScope.php       -- auto-filter by parent hierarchy
│   │   └── Requests/
│   ├── Models/
│   │   ├── User.php
│   │   ├── SipAccount.php
│   │   ├── Trunk.php
│   │   ├── TrunkRoute.php
│   │   ├── Did.php
│   │   ├── RateGroup.php
│   │   ├── Rate.php
│   │   ├── Cdr.php
│   │   ├── RatedCdr.php
│   │   ├── Transaction.php
│   │   ├── Invoice.php
│   │   └── Asterisk/                  -- Asterisk realtime models
│   │       ├── PsEndpoint.php
│   │       ├── PsAuth.php
│   │       ├── PsAor.php
│   │       └── PsContact.php
│   ├── Services/
│   │   ├── Asterisk/
│   │   │   ├── EndpointService.php    -- CRUD ps_endpoints/ps_auths/ps_aors
│   │   │   ├── TrunkProvisionService.php -- Generate pjsip_trunks.conf & reload
│   │   │   ├── TrunkRouteService.php  -- Prefix-based outgoing trunk selection
│   │   │   ├── AmiService.php         -- AMI connection for live monitoring
│   │   │   └── DialplanService.php    -- Generate/reload dialplan if needed
│   │   ├── Billing/
│   │   │   ├── RatingEngine.php       -- Longest prefix match, cost calculation
│   │   │   ├── BalanceService.php     -- Credit/debit operations (atomic)
│   │   │   ├── CdrProcessor.php       -- Process raw CDR → rated_cdr
│   │   │   └── InvoiceGenerator.php   -- Monthly invoice creation
│   │   └── Did/
│   │       └── DidService.php
│   ├── Jobs/
│   │   ├── ProcessCdrJob.php          -- Runs every 30s via scheduler
│   │   ├── GenerateInvoicesJob.php    -- Monthly
│   │   ├── SuspendOverdueJob.php      -- Check postpaid limits
│   │   └── ProvisionEndpointJob.php
│   ├── Scopes/
│   │   └── TenantScope.php           -- Global scope for hierarchy filtering
│   ├── Observers/
│   │   └── SipAccountObserver.php    -- Sync to Asterisk realtime on create/update/delete
│   └── Console/
│       └── Commands/
│           ├── ProcessCdrCommand.php
│           ├── GenerateInvoicesCommand.php
│           └── AsteriskHealthCheckCommand.php
├── agi/
│   ├── server.php                     -- FastAGI daemon entry point
│   ├── handlers/
│   │   ├── CheckBalanceHandler.php
│   │   └── RouteInboundHandler.php
│   └── bootstrap.php                  -- Laravel app bootstrap for AGI context
├── database/
│   └── migrations/
│       ├── 0001_create_users_table.php
│       ├── 0002_create_sip_accounts_table.php
│       ├── 0003_create_trunks_table.php
│       ├── 0003b_create_trunk_routes_table.php
│       ├── 0004_create_dids_table.php
│       ├── 0005_create_rate_groups_table.php
│       ├── 0006_create_rates_table.php
│       ├── 0007_create_cdr_table.php
│       ├── 0008_create_rated_cdr_table.php
│       ├── 0009_create_transactions_table.php
│       ├── 0010_create_invoices_table.php
│       └── 0011_create_asterisk_realtime_tables.php
├── routes/
│   ├── web.php
│   └── api.php
├── resources/views/                    -- Blade templates (or Livewire/Inertia)
├── config/
│   └── asterisk.php                   -- AMI host, AGI port, etc.
└── asterisk/                          -- Asterisk config templates
    ├── pjsip.conf                     -- base transport + global settings
    ├── pjsip_trunks.conf              -- auto-generated by Laravel (trunk endpoints)
    ├── extensions.conf                -- dialplan (from-internal, from-trunk, from-internal-local)
    ├── cdr_adaptive_odbc.conf
    ├── res_odbc.conf
    └── extconfig.conf
```

### Key Service Logic

#### RatingEngine — Longest Prefix Match
```php
// Find rate: ORDER BY LENGTH(prefix) DESC, match longest prefix first
SELECT * FROM rates
WHERE rate_group_id = ?
  AND ? LIKE CONCAT(prefix, '%')
  AND status = 'active'
ORDER BY LENGTH(prefix) DESC
LIMIT 1;
```

#### BalanceService — Atomic Balance Operations
```php
// Use DB transaction + row locking for atomic balance updates
DB::transaction(function () {
    $user = User::lockForUpdate()->find($userId);
    $user->balance += $amount;  // negative for charges
    $user->save();
    Transaction::create([...]);
});
```

#### SipAccount Observer — Asterisk Sync
When a SipAccount is created/updated/deleted, the observer writes to `ps_endpoints`, `ps_auths`, and `ps_aors` tables, then optionally reloads PJSIP via AMI.

### Role-Based Access

Using **Spatie Laravel Permission** or custom middleware:

| Feature | Admin | Reseller | Client |
|---|---|---|---|
| Manage resellers | Yes | No | No |
| Manage trunks (in/out/both) | Yes | No | No |
| Manage trunk routes | Yes | No | No |
| Manage all DIDs | Yes | No | No |
| Create clients | Yes | Yes (own) | No |
| Create SIP accounts | Yes | Yes (own clients) | Limited |
| View CDR | All | Own tree | Own |
| Manage rates | All groups | Own groups | No |
| Top-up balance | Any user | Own clients | No |
| View invoices | All | Own tree | Own |
| System settings | Yes | No | No |

---

## 4. Implementation Phases

### Phase 1: Foundation
- Laravel project setup (auth, roles, middleware)
- Database migrations (all tables)
- User CRUD with hierarchy (Admin creates Resellers, Resellers create Clients)
- Tenant scoping (users only see their own subtree)
- Basic UI layout with role-based navigation
- Asterisk installation and base configuration
- MySQL ODBC connector setup for Asterisk Realtime
- **Deliverable**: Login, user management with hierarchy, Asterisk running

### Phase 2: Core Switching
- SIP Account CRUD → auto-provision to Asterisk realtime tables (ps_endpoints/ps_auths/ps_aors)
- **Trunk management (incoming + outgoing + both)**:
  - Admin: add/edit/delete trunks with direction (incoming/outgoing/both)
  - Auto-generate `pjsip_trunks.conf` with endpoint, auth, AOR, identify, registration sections
  - Reload PJSIP via AMI after trunk changes
- **Trunk routing rules**: prefix-based outgoing trunk selection with priority/failover
- Basic dialplan:
  - Outbound: SIP account → AGI selects outgoing trunk by prefix → Dial via trunk
  - Inbound: incoming trunk → AGI matches DID → routes to SIP account
  - Internal: SIP-to-SIP direct calls
- DID management and assignment (linked to incoming/both trunks)
- SIP registration monitoring via AMI (both SIP accounts and trunk registrations)
- CDR collection via `cdr_adaptive_odbc` into MySQL
- Basic CDR viewer (raw, unrated)
- **Deliverable**: Working calls — SIP accounts register, outbound via trunks, inbound via DIDs

### Phase 3: Billing Engine
- Rate group and rate management (prefix-based)
- Rating engine (longest prefix match)
- CDR processor job (raw CDR → rated CDR with costs)
- Balance service (atomic credit/debit)
- Prepaid: AGI balance check before call, max duration enforcement
- Postpaid: credit limit enforcement
- Transaction log
- Top-up functionality (admin/reseller adds credit to accounts)
- **Deliverable**: Calls are rated, balances deducted, prepaid cutoff works

### Phase 4: Business Features
- Reseller rate groups (reseller creates own rates with markup over admin base rate)
- Reseller credit system (admin allocates credit to reseller, reseller allocates to clients)
- Invoice generation (monthly, for postpaid accounts)
- Invoice PDF export
- DID monthly billing (recurring charges)
- Account suspension on zero balance (prepaid) or over credit limit (postpaid)
- **Deliverable**: Full reseller workflow, invoicing, automated suspension

### Phase 5: Dashboards & Monitoring
- Admin dashboard (total calls, revenue, active channels, system health)
- Reseller dashboard (own clients stats, revenue, balance)
- Client dashboard (call stats, balance, recent calls)
- Live channel monitor (active calls via AMI)
- CDR export (CSV/Excel)
- Call quality metrics (ASR, ACD)
- Asterisk health check command
- **Deliverable**: Production-ready MVP with monitoring

---

## 5. Key Technical Decisions

| Decision | Choice | Rationale |
|---|---|---|
| SIP driver | PJSIP (not chan_sip) | chan_sip is deprecated in Asterisk 21 |
| SIP provisioning | Asterisk Realtime (ARA) via MySQL | No config file rewrites; instant provisioning |
| Billing gate | FastAGI (PHP) | Direct access to Laravel's DB/services; low latency |
| CDR collection | cdr_adaptive_odbc | Native Asterisk module; writes directly to MySQL |
| CDR processing | Laravel Queue Job (every 30s) | Async; doesn't block calls; scalable |
| Balance tracking | MySQL with row-level locking + Redis cache | Atomic operations; Redis for fast AGI reads |
| Auth/permissions | Spatie Laravel Permission | Battle-tested; supports role hierarchy |
| Frontend | Blade + Livewire (or Inertia + Vue) | Rapid development for MVP |
| Trunk direction | Single table with direction ENUM | Avoids duplicate schemas; one trunk can handle both directions |
| Outgoing trunk selection | Prefix-based routing with priority + failover | Longest prefix match on trunk_routes; AGI returns primary + backup trunk |
| Incoming trunk auth | IP-based (identify) or registration | Flexible per provider; most ITSP use IP-based |
| Trunk provisioning | Laravel generates pjsip_trunks.conf + AMI reload | Keeps trunk config in sync; no manual Asterisk editing |

---

## 6. Server Requirements (MVP)

- **OS**: Ubuntu 22.04/24.04 LTS or AlmaLinux 9
- **CPU**: 4 cores minimum
- **RAM**: 8 GB minimum
- **Storage**: 50GB+ SSD (CDR data grows)
- **Ports**: 5060/UDP (SIP), 10000-20000/UDP (RTP), 80/443 (Web)
- **Software**: PHP 8.3, Composer, Node.js 20, MySQL 8, Redis, Supervisor, Asterisk 21

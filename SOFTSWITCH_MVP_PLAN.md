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
├── destination_type ENUM('sip_account','ring_group','external')
│                                       -- sip_account: Flow 3 (rings a SIP phone)
│                                       -- ring_group: Flow 3 variant (rings multiple SIP phones)
│                                       -- external: Flow 4 (forwards to PSTN via outbound trunk)
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
├── sip_account_id (FK→sip_accounts.id) NULL  -- NULL for Flow 4 (trunk→trunk)
├── user_id (FK→users.id)           -- billed client (or DID owner for Flow 4)
├── reseller_id (FK→users.id) NULL  -- reseller (for commission tracking)
├── call_flow ENUM('sip_to_trunk','sip_to_sip','trunk_to_sip','trunk_to_trunk')
│                                    -- maps to Flow 1, 2, 3, 4
├── caller VARCHAR(40)
├── callee VARCHAR(40)
├── destination VARCHAR(100)        -- matched destination name
├── matched_prefix VARCHAR(20)
├── incoming_trunk_id (FK→trunks.id) NULL  -- trunk call arrived on (Flow 3 & 4)
├── outgoing_trunk_id (FK→trunks.id) NULL  -- trunk call went out on (Flow 1 & 4)
├── did_id (FK→dids.id) NULL        -- DID involved (Flow 3 & 4)
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
├── INDEX idx_call_flow (call_flow, call_start)

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

### Call Flow Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    4 SUPPORTED CALL FLOWS                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  FLOW 1: SIP Account ──→ Outbound Trunk ──→ PSTN           │
│          (Client dials external number)                     │
│                                                             │
│  FLOW 2: SIP Account ──→ SIP Account (same server)         │
│          (Internal extension-to-extension call)             │
│                                                             │
│  FLOW 3: Inbound Trunk ──→ SIP Account                     │
│          (PSTN caller reaches a DID → rings SIP phone)      │
│                                                             │
│  FLOW 4: Inbound Trunk ──→ Outbound Trunk                  │
│          (DID forwards to external PSTN number)             │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Dialplan (`extensions.conf`)

All SIP accounts use context `from-internal`. All incoming trunks use context `from-trunk`.
A single AGI call per context handles routing decisions.

```ini
; =============================================================
; CONTEXT: from-internal
; SOURCE:  SIP accounts (registered endpoints)
; HANDLES: Flow 1 (SIP→Trunk) and Flow 2 (SIP→SIP)
; =============================================================
[from-internal]

exten => _X.,1,NoOp(== SIP call from ${CHANNEL(endpoint)} to ${EXTEN} ==)
 same => n,Set(ACCOUNTCODE=${CHANNEL(endpoint)})

 ; --- AGI does ALL routing decisions ---
 ; 1. Checks if ${EXTEN} matches a local SIP account username
 ; 2. If local  → sets ROUTE_TYPE=local, DEST_ENDPOINT=<sip_username>
 ; 3. If external→ checks balance, finds rate, selects outgoing trunk
 ;                 sets ROUTE_TYPE=trunk, TRUNK_1, TRUNK_2, MAX_DURATION, etc.
 ; 4. If denied  → sets ROUTE_TYPE=denied
 same => n,AGI(agi://127.0.0.1:4573,route_outbound,${CHANNEL(endpoint)},${EXTEN})

 same => n,GotoIf($["${ROUTE_TYPE}" = "local"]?local)
 same => n,GotoIf($["${ROUTE_TYPE}" = "trunk"]?trunk)
 same => n,Goto(denied)

 ; --- FLOW 2: SIP Account → SIP Account (local) ---
 same => n(local),NoOp(Local call to ${DEST_ENDPOINT})
 same => n,Dial(PJSIP/${DEST_ENDPOINT},30,Tt)
 same => n,Goto(hangup)

 ; --- FLOW 1: SIP Account → Outbound Trunk (PSTN) ---
 same => n(trunk),NoOp(Trunk call via ${TRUNK_1} to ${DIAL_NUM})
 same => n,Set(CDR(userfield)=${RATE_ID})
 same => n,Set(TIMEOUT(absolute)=${MAX_DURATION})
 same => n,Set(DIAL_NUM=${TRUNK_PREFIX}${EXTEN:${TRUNK_STRIP}})
 ; Primary trunk
 same => n,Dial(PJSIP/${DIAL_NUM}@${TRUNK_1},60,Tt)
 same => n,NoOp(Primary trunk result: ${DIALSTATUS})
 same => n,GotoIf($["${DIALSTATUS}" = "ANSWER"]?done)
 ; Failover trunk
 same => n,GotoIf($["${TRUNK_2}" = ""]?hangup)
 same => n,NoOp(Failover to ${TRUNK_2})
 same => n,Dial(PJSIP/${DIAL_NUM}@${TRUNK_2},60,Tt)
 same => n,Goto(done)

 ; --- Denied (no balance / no route) ---
 same => n(denied),Playback(ss-noservice)
 same => n,Goto(hangup)

 same => n(done),Hangup()
 same => n(hangup),Hangup()


; =============================================================
; CONTEXT: from-trunk
; SOURCE:  Incoming PSTN trunks
; HANDLES: Flow 3 (Trunk→SIP) and Flow 4 (Trunk→Trunk)
; =============================================================
[from-trunk]

exten => _X.,1,NoOp(== Inbound from trunk ${CHANNEL(endpoint)} DID ${EXTEN} ==)
 same => n,Set(ACCOUNTCODE=trunk-${CHANNEL(endpoint)})

 ; --- AGI does ALL routing decisions ---
 ; 1. Validates trunk is incoming/both
 ; 2. Looks up DID in dids table by ${EXTEN}
 ; 3. Finds destination: sip_account, ring_group, or external forward
 ; 4. If external → also selects outgoing trunk for forwarding
 ; 5. Checks DID owner has balance (if forwarding to external charges apply)
 same => n,AGI(agi://127.0.0.1:4573,route_inbound,${CHANNEL(endpoint)},${EXTEN})

 same => n,GotoIf($["${ROUTE_TYPE}" = "sip"]?sip)
 same => n,GotoIf($["${ROUTE_TYPE}" = "ring_group"]?ringgroup)
 same => n,GotoIf($["${ROUTE_TYPE}" = "trunk_forward"]?forward)
 same => n,Goto(no_route)

 ; --- FLOW 3: Inbound Trunk → SIP Account ---
 same => n(sip),NoOp(Route to SIP endpoint ${DEST_ENDPOINT})
 same => n,Dial(PJSIP/${DEST_ENDPOINT},30,Tt)
 same => n,Goto(hangup)

 ; --- FLOW 3 variant: Inbound Trunk → Ring Group ---
 same => n(ringgroup),NoOp(Route to ring group ${DEST_ENDPOINTS})
 same => n,Dial(${DEST_ENDPOINTS},30,Tt)
 same => n,Goto(hangup)

 ; --- FLOW 4: Inbound Trunk → Outbound Trunk (call forwarding) ---
 same => n(forward),NoOp(Forward DID to ${DEST_NUMBER} via trunk ${DEST_TRUNK})
 same => n,Set(TIMEOUT(absolute)=${MAX_DURATION})
 same => n,Set(CDR(userfield)=${RATE_ID})
 same => n,Dial(PJSIP/${DEST_NUMBER}@${DEST_TRUNK},60,Tt)
 same => n,Goto(hangup)

 ; --- No route found ---
 same => n(no_route),NoOp(No route for DID ${EXTEN})
 same => n,Playback(ss-noservice)

 same => n(hangup),Hangup()
```

### AGI Billing & Routing Script (PHP FastAGI)

A PHP FastAGI daemon (using `phpagi` or `PAGI` library) running on port 4573.
**Two AGI handlers** — one per dialplan context. Each handler is the single decision point.

```
═══════════════════════════════════════════════════════════════
AGI HANDLER 1: route_outbound(endpoint, destination)
Called from: [from-internal] context
Handles:     Flow 1 (SIP→Trunk) and Flow 2 (SIP→SIP)
═══════════════════════════════════════════════════════════════

Step 1 — Identify caller
  → Look up SIP account by endpoint name
  → Get client user, reseller, billing_type, balance, rate_group_id

Step 2 — Check if destination is a LOCAL SIP account
  → SELECT * FROM sip_accounts WHERE username = ${destination}
    AND status = 'active'
  → If FOUND and same server:
      Set ROUTE_TYPE = "local"
      Set DEST_ENDPOINT = sip_account.username
      RETURN (no billing check for internal calls)

Step 3 — External call: rate lookup
  → Longest prefix match on rates table using caller's rate_group_id
  → If NO rate found → Set ROUTE_TYPE = "denied", RETURN

Step 4 — External call: balance check
  → Prepaid:  max_duration = (balance / rate_per_minute) * 60
              If balance <= 0 → DENIED
  → Postpaid: remaining = credit_limit - outstanding_charges
              If remaining <= 0 → DENIED
  → Set MAX_DURATION (seconds)

Step 5 — External call: select outgoing trunk
  → Longest prefix match on trunk_routes table:
    SELECT t.*, tr.prefix, tr.priority, tr.weight
    FROM trunk_routes tr
    JOIN trunks t ON t.id = tr.trunk_id
    WHERE ${destination} LIKE CONCAT(tr.prefix, '%')
      AND t.status = 'active'
      AND t.direction IN ('outgoing','both')
    ORDER BY LENGTH(tr.prefix) DESC, tr.priority ASC
  → Check trunk capacity (active channels < max_channels via AMI)
  → Pick TRUNK_1 (primary) and TRUNK_2 (failover)
  → If NO trunk found → DENIED

Step 6 — Return variables
  → ROUTE_TYPE  = "trunk"
  → TRUNK_1     = trunk endpoint name (e.g. "trunk-outgoing-1")
  → TRUNK_2     = failover trunk endpoint name (or empty)
  → TRUNK_PREFIX= trunk's tech prefix
  → TRUNK_STRIP = trunk's strip_digits
  → RATE_ID     = matched rate ID
  → MAX_DURATION= max call duration in seconds


═══════════════════════════════════════════════════════════════
AGI HANDLER 2: route_inbound(trunk_endpoint, did_number)
Called from: [from-trunk] context
Handles:     Flow 3 (Trunk→SIP) and Flow 4 (Trunk→Trunk)
═══════════════════════════════════════════════════════════════

Step 1 — Validate incoming trunk
  → Look up trunk by endpoint name
  → Verify direction is 'incoming' or 'both'
  → If invalid → Set ROUTE_TYPE = "denied", RETURN

Step 2 — Look up DID
  → SELECT * FROM dids WHERE number = ${did_number}
    AND trunk_id = trunk.id AND status = 'active'
  → If NOT found → Set ROUTE_TYPE = "denied", RETURN

Step 3 — Get DID destination
  → Read destination_type and destination_id from dids table
  → Look up the DID owner (assigned_to_user_id)

Step 4 — Route by destination type

  IF destination_type = "sip_account":
    → FLOW 3: Inbound Trunk → SIP Account
    → Look up SIP account, verify it's active
    → Set ROUTE_TYPE = "sip"
    → Set DEST_ENDPOINT = sip_account.username
    → RETURN

  IF destination_type = "ring_group":
    → FLOW 3 variant: Inbound Trunk → Multiple SIP Accounts
    → Build dial string: PJSIP/ext1&PJSIP/ext2&PJSIP/ext3
    → Set ROUTE_TYPE = "ring_group"
    → Set DEST_ENDPOINTS = dial string
    → RETURN

  IF destination_type = "external":
    → FLOW 4: Inbound Trunk → Outbound Trunk (forwarding)
    → Check DID owner's balance (they pay for the forward leg)
    → Rate lookup for the external destination number
    → If no balance or no rate → DENIED
    → Select outgoing trunk (same logic as route_outbound Step 5)
    → Set ROUTE_TYPE = "trunk_forward"
    → Set DEST_NUMBER = external number
    → Set DEST_TRUNK  = outgoing trunk endpoint name
    → Set MAX_DURATION = based on DID owner's balance
    → Set RATE_ID = matched rate
    → RETURN
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
│   │   ├── RouteOutboundHandler.php   -- Flow 1 & 2: SIP→Trunk or SIP→SIP
│   │   └── RouteInboundHandler.php    -- Flow 3 & 4: Trunk→SIP or Trunk→Trunk
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

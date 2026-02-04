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
├── kyc_status ENUM('not_submitted','pending','approved','rejected') DEFAULT 'not_submitted'
├── kyc_verified_at TIMESTAMP NULL
├── kyc_rejected_reason VARCHAR(255) NULL
├── billing_type ENUM('prepaid','postpaid')
├── credit_limit DECIMAL(12,4) DEFAULT 0  -- for postpaid
├── balance DECIMAL(12,4) DEFAULT 0       -- current balance
├── currency VARCHAR(3) DEFAULT 'USD'
├── rate_group_id (FK→rate_groups.id)
├── -- Balance & call limits
├── min_balance_for_calls DECIMAL(10,4) DEFAULT 0.00  -- minimum balance required to place a call
├──   -- e.g. 1.00 = user needs at least $1.00 to dial (prevents micro-balance abuse)
├── low_balance_threshold DECIMAL(10,4) DEFAULT 5.00  -- trigger LowBalanceNotification below this
├── -- Security & fraud prevention
├── max_channels INT UNSIGNED DEFAULT 10     -- max concurrent calls (all SIP accounts combined)
├── daily_spend_limit DECIMAL(10,4) NULL     -- auto-suspend if exceeded (NULL = no limit)
├── daily_call_limit INT UNSIGNED NULL       -- max calls/day (NULL = no limit)
├── destination_whitelist_enabled BOOLEAN DEFAULT FALSE  -- restrict to whitelisted prefixes
├── -- Two-factor authentication
├── two_fa_enabled BOOLEAN DEFAULT FALSE
├── two_fa_secret VARCHAR(255) NULL          -- encrypted TOTP secret
├── two_fa_recovery_codes JSON NULL          -- encrypted backup codes
├── two_fa_confirmed_at TIMESTAMP NULL
├── created_at, updated_at
```

**Hierarchy enforcement**: `parent_id` links Client→Reseller→Admin. Admin has `parent_id = NULL`. Reseller's `parent_id` = Admin. Client's `parent_id` = Reseller.

**KYC enforcement**: Resellers and clients must complete KYC before they can create SIP accounts, make calls, or access billing features. Admin can override.

### KYC (Know Your Customer)

```sql
-- KYC profile (one per reseller/client)
kyc_profiles
├── id (PK)
├── user_id (FK→users.id, UNIQUE)    -- reseller or client
├── -- Personal / Company Info
├── account_type ENUM('individual','company')
├── full_name VARCHAR(150)            -- or company name
├── contact_person VARCHAR(150) NULL  -- for company accounts
├── phone VARCHAR(20)
├── alt_phone VARCHAR(20) NULL
├── address_line1 VARCHAR(255)
├── address_line2 VARCHAR(255) NULL
├── city VARCHAR(100)
├── state VARCHAR(100) NULL
├── postal_code VARCHAR(20)
├── country VARCHAR(2)                -- ISO 3166-1 alpha-2
├── -- Identity Info
├── id_type ENUM('national_id','passport','driving_license','business_license')
├── id_number VARCHAR(50)
├── id_expiry_date DATE NULL
├── -- Submitted/Review timestamps
├── submitted_at TIMESTAMP NULL
├── reviewed_at TIMESTAMP NULL
├── reviewed_by (FK→users.id) NULL   -- admin who reviewed
├── created_at, updated_at

-- KYC documents (multiple files per KYC profile)
kyc_documents
├── id (PK)
├── kyc_profile_id (FK→kyc_profiles.id)
├── document_type ENUM('id_front','id_back','selfie','proof_of_address',
│                       'business_registration','tax_certificate','other')
├── file_path VARCHAR(500)            -- stored in storage/app/kyc/{user_id}/
├── original_name VARCHAR(255)
├── mime_type VARCHAR(50)
├── file_size INT                     -- bytes
├── status ENUM('uploaded','accepted','rejected') DEFAULT 'uploaded'
├── rejection_reason VARCHAR(255) NULL
├── created_at, updated_at
├── INDEX idx_kyc_profile (kyc_profile_id)
```

**Required documents by account type:**

| Document | Individual | Company |
|---|---|---|
| ID front (national_id / passport / license) | Required | Required |
| ID back | Required (if national_id) | Required (if national_id) |
| Selfie (holding ID) | Required | Required |
| Proof of address (utility bill / bank statement) | Required | Required |
| Business registration certificate | -- | Required |
| Tax certificate | -- | Optional |

**KYC Workflow:**
```
User registers → kyc_status = 'not_submitted'
    │
    ▼
User fills KYC form + uploads documents → kyc_status = 'pending'
    │
    ▼
Admin reviews ──→ Approve → kyc_status = 'approved', kyc_verified_at = now()
    │                         Account fully unlocked
    │
    └──→ Reject → kyc_status = 'rejected', kyc_rejected_reason = "..."
                   User can re-submit with corrections
```

### SIP Accounts (Asterisk Realtime - PJSIP)

```sql
-- sip_accounts (application table, linked to Asterisk realtime)
sip_accounts
├── id (PK)
├── user_id (FK→users.id, the client who owns this)
├── username VARCHAR(40) UNIQUE     -- SIP username / endpoint name
├── password VARCHAR(80)            -- SIP auth password
├── -- Authentication mode
├── auth_type ENUM('password','ip','both') DEFAULT 'password'
├──   -- password: standard SIP digest auth (username + password)
├──   -- ip: IP-based authentication (no password needed, identified by source IP)
├──   -- both: require password AND source IP match
├── allowed_ips VARCHAR(500) NULL   -- comma-separated IPs/CIDRs for ip/both auth
├──   -- e.g. '192.168.1.100,10.0.0.0/24' (NULL = any IP, only for password auth)
├── -- Caller ID
├── caller_id_name VARCHAR(80)
├── caller_id_number VARCHAR(20)
├── -- Limits & codecs
├── max_channels INT DEFAULT 2
├── codec_allow VARCHAR(100) DEFAULT 'ulaw,alaw,g729'
├── -- Status & registration
├── status ENUM('active','suspended','disabled')
├── last_registered_at TIMESTAMP NULL
├── last_registered_ip VARCHAR(45) NULL
├── created_at, updated_at

-- Asterisk PJSIP Realtime Tables (managed by Asterisk Alembic migrations)
ps_endpoints       -- populated by Laravel when SIP account is created
ps_auths           -- auth credentials (only created if auth_type = 'password' or 'both')
ps_aors            -- address of record
ps_contacts        -- registered contacts (written by Asterisk)
ps_endpoint_id_ips -- IP-based identification (created if auth_type = 'ip' or 'both')
```

**SIP Account Authentication Modes:**
```
┌───────────────┬──────────────────────────────────────────────────┐
│ auth_type     │ How it works in Asterisk PJSIP                   │
├───────────────┼──────────────────────────────────────────────────┤
│ password      │ Standard digest auth via ps_auths table.         │
│               │ Any IP can register with correct username/pass.  │
│               │ If allowed_ips is set → ALSO restrict source IP  │
│               │ via ps_endpoint_id_ips (double protection).      │
│               │                                                  │
│ ip            │ No password needed. Endpoint identified by       │
│               │ source IP via ps_endpoint_id_ips (identify).     │
│               │ allowed_ips is REQUIRED.                         │
│               │ Typical for PBX trunking (office IP known).      │
│               │ No ps_auths entry created.                       │
│               │                                                  │
│ both          │ Require password auth AND source IP match.       │
│               │ Both ps_auths and ps_endpoint_id_ips created.    │
│               │ Highest security — used for high-value accounts. │
└───────────────┴──────────────────────────────────────────────────┘

PJSIP provisioning by EndpointService:

  auth_type = 'password':
    → INSERT ps_auths (username, password)
    → INSERT ps_endpoints (auth = <endpoint>-auth)
    → IF allowed_ips SET:
        INSERT ps_endpoint_id_ips (endpoint, match = <each_ip>)

  auth_type = 'ip':
    → INSERT ps_endpoint_id_ips (endpoint, match = <each_ip>)
    → INSERT ps_endpoints (NO auth reference)
    → NO ps_auths entry

  auth_type = 'both':
    → INSERT ps_auths (username, password)
    → INSERT ps_endpoint_id_ips (endpoint, match = <each_ip>)
    → INSERT ps_endpoints (auth = <endpoint>-auth)
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
├── outgoing_priority INT DEFAULT 1  -- failover ordering for outgoing
├── -- Dial string manipulation (per-trunk number format rules)
├── dial_pattern_match VARCHAR(50) NULL     -- regex pattern to match dialed number (NULL = match all)
├── dial_pattern_replace VARCHAR(50) NULL   -- replacement pattern for matched number
├── dial_prefix VARCHAR(20) NULL            -- prefix to prepend AFTER pattern replacement
├── dial_strip_digits INT DEFAULT 0         -- digits to strip from LEFT of dialed number
├── tech_prefix VARCHAR(20) NULL            -- tech prefix for provider identification
├── -- Caller ID / CLI manipulation (per-trunk outgoing rules)
├── cli_mode ENUM('passthrough','override','prefix_strip','translate','hide') DEFAULT 'passthrough'
├── cli_override_number VARCHAR(40) NULL    -- fixed CLI when cli_mode='override'
├── cli_prefix_strip INT DEFAULT 0          -- strip N digits from caller ID left
├── cli_prefix_add VARCHAR(20) NULL         -- prepend to caller ID after stripping
├── -- Incoming-specific fields
├── incoming_context VARCHAR(80) DEFAULT 'from-trunk'  -- Asterisk dialplan context
├── incoming_auth_type ENUM('ip','registration','both') DEFAULT 'ip'
├── incoming_ip_acl VARCHAR(255) NULL  -- allowed source IPs (comma-separated CIDRs)
├── -- Health monitoring
├── health_check BOOLEAN DEFAULT TRUE        -- enable SIP OPTIONS ping
├── health_check_interval INT DEFAULT 60     -- seconds between OPTIONS pings
├── health_status ENUM('up','down','degraded','unknown') DEFAULT 'unknown'
├── health_last_checked_at TIMESTAMP NULL
├── health_last_up_at TIMESTAMP NULL
├── health_fail_count INT DEFAULT 0          -- consecutive failures
├── health_auto_disable_threshold INT DEFAULT 5  -- auto-disable after N failures
├── health_asr_threshold DECIMAL(5,2) NULL   -- auto-flag if ASR drops below (e.g. 30.00%)
├── -- Common fields
├── status ENUM('active','disabled','auto_disabled')
├── notes TEXT NULL
├── created_at, updated_at
├── INDEX idx_direction (direction, status)
├── INDEX idx_outgoing_priority (outgoing_priority)
├── INDEX idx_health (health_status)
```

**Direction logic:**
- `incoming` — receives calls from PSTN provider (DIDs routed through this trunk)
- `outgoing` — sends calls to PSTN provider (used for outbound dialing)
- `both` — single trunk handles both directions (common with SIP trunk providers)

**Dial String Manipulation (per-trunk number formatting):**

Different trunks expect different number formats. The manipulation pipeline runs in order:
```
Original dialed number
  │
  ├─ 1. dial_strip_digits — strip N digits from left
  ├─ 2. dial_pattern_match/replace — regex transform (if set)
  ├─ 3. dial_prefix — prepend prefix
  └─ 4. tech_prefix — prepend tech prefix (provider identification)
  │
  Final DIAL_NUM sent to trunk
```

**Examples:**
```
┌─────────────────────────────────────────────────────────────────┐
│  User dials: 8801712345678                                       │
├──────────────┬──────────────────────┬───────────────────────────┤
│ Trunk        │ Manipulation Config  │ Final DIAL_NUM            │
├──────────────┼──────────────────────┼───────────────────────────┤
│ Provider A   │ strip=0, prefix=+    │ +8801712345678 (E.164)   │
│ Provider B   │ strip=2, prefix=00   │ 008801712345678 (intl)   │
│ Provider C   │ strip=3, prefix=""   │ 01712345678 (national)   │
│ Provider D   │ pattern: ^880(.*)    │ $1 → 1712345678 (local)  │
│              │ replace: $1          │                           │
│ Provider E   │ strip=0, tech=9999#  │ 9999#8801712345678       │
└──────────────┴──────────────────────┴───────────────────────────┘
```

**Caller ID / CLI Manipulation (per-trunk outgoing rules):**

Controls what Caller ID is sent to the trunk provider on outgoing calls.

```
┌───────────────┬────────────────────────────────────────────────┐
│ cli_mode      │ Behavior                                       │
├───────────────┼────────────────────────────────────────────────┤
│ passthrough   │ Pass original caller ID unchanged (default)    │
│ override      │ Replace with cli_override_number for all calls │
│ prefix_strip  │ Strip cli_prefix_strip digits from left,       │
│               │ then prepend cli_prefix_add                    │
│ translate     │ Apply strip + add (e.g. +880→00880)            │
│ hide          │ Send anonymous / no caller ID (CLIR)           │
└───────────────┴────────────────────────────────────────────────┘

Examples:
  SIP account caller_id = "8801712345678"

  passthrough:   → 8801712345678 (sent as-is)
  override:      → 18005551234 (cli_override_number)
  prefix_strip:  → strip 3 digits "01712345678", add "+880" → +88001712345678
  translate:     → strip 0, add "+" → +8801712345678
  hide:          → Anonymous (Privacy: id header set)
```

**Trunk Health Monitoring (SIP OPTIONS):**
```
┌──────────────────────────────────────────────────────────────┐
│                   TRUNK HEALTH MONITORING                      │
├──────────────────────────────────────────────────────────────┤
│                                                                │
│  TrunkHealthCheckCommand (runs every 60 seconds):             │
│  ├── For each trunk WHERE health_check = true:                │
│  │   1. Send SIP OPTIONS to trunk host:port via AMI           │
│  │   2. Wait for response (timeout 5 seconds)                 │
│  │   3. If 200 OK → health_status = 'up', reset fail_count   │
│  │   4. If timeout/error → increment health_fail_count        │
│  │   5. If fail_count >= health_auto_disable_threshold:       │
│  │      → Set status = 'auto_disabled'                        │
│  │      → Remove from trunk_routes selection                  │
│  │      → Send TrunkDownNotification to admin                 │
│  │   6. If trunk recovers (was down, now up):                 │
│  │      → Set status = 'active', health_status = 'up'        │
│  │      → Send TrunkRecoveredNotification to admin            │
│  │                                                            │
│  ASR-based degradation monitoring:                             │
│  ├── Every 5 min: check trunk's ASR from Redis counters       │
│  │   asr = stats:trunk:{id}:{hour}.answered_calls /           │
│  │         stats:trunk:{id}:{hour}.total_calls * 100          │
│  ├── If asr < health_asr_threshold:                           │
│  │   → Set health_status = 'degraded'                         │
│  │   → Send TrunkDegradedNotification (ASR alert)             │
│  │   → Trunk still active but flagged for admin review        │
│  │                                                            │
│  Redis keys for trunk health:                                  │
│  ├── trunk_health:{trunk_id} = {status, last_check, fail_count}│
│  └── Dashboards show trunk health status in real-time          │
│                                                                │
└──────────────────────────────────────────────────────────────┘
```

**Trunk routing table** for outgoing — maps destination prefixes + time windows to specific outgoing trunks:

```sql
trunk_routes
├── id (PK)
├── trunk_id (FK→trunks.id)          -- must be 'outgoing' or 'both' direction
├── prefix VARCHAR(20)               -- destination prefix to match (e.g. '1', '44', '88017')
├── -- Time-based routing (NULL = always active, no time restriction)
├── time_start TIME NULL             -- e.g. '00:00:00' (inclusive)
├── time_end TIME NULL               -- e.g. '06:00:00' (exclusive)
├── days_of_week VARCHAR(20) NULL    -- e.g. 'mon,tue,wed,thu,fri' (NULL = all days)
├── timezone VARCHAR(50) DEFAULT 'UTC'  -- timezone for time evaluation
├── -- Priority & balancing
├── priority INT DEFAULT 1           -- lower = higher priority (for failover)
├── weight INT DEFAULT 100           -- load balancing weight among same-priority trunks
├── status ENUM('active','disabled')
├── created_at, updated_at
├── INDEX idx_prefix_priority (prefix, priority)
├── INDEX idx_prefix_time (prefix, time_start, time_end)
```

**Routing logic** — prefix match + time window + priority + failover:

1. Match longest prefix first
2. Filter by current time (if `time_start`/`time_end` are set)
3. Order by priority ASC (lower = preferred)
4. Pick primary + failover trunk

**Time-based routing examples:**

```
┌──────────┬────────────────────┬────────────────┬──────────┐
│ prefix   │ time window        │ trunk          │ priority │
├──────────┼────────────────────┼────────────────┼──────────┤
│ 88017    │ 00:00 AM → 06:00 AM│ Trunk 1 (cheap)│ 1        │
│ 88017    │ 06:00 AM → 12:00 PM│ Trunk 2 (mid)  │ 1        │
│ 88017    │ 12:00 PM → 06:00 PM│ Trunk 3 (peak) │ 1        │
│ 88017    │ 06:00 PM → 12:00 AM│ Trunk 1 (cheap)│ 1        │
│ 88017    │ NULL (any time)    │ Trunk 4 (backup)│ 2       │ ← failover
│ 44       │ NULL (any time)    │ Trunk 5        │ 1        │
│ 1        │ NULL (any time)    │ Trunk 6        │ 1        │
└──────────┴────────────────────┴────────────────┴──────────┘
```

**How it works for prefix `88017` at 3:00 AM:**
- Matches prefix `88017`
- Current time 3:00 AM falls in `00:00–06:00` window → Trunk 1 (priority 1)
- Failover: Trunk 4 has `NULL` time (always active) + priority 2 → backup
- AGI returns: TRUNK_1 = Trunk 1, TRUNK_2 = Trunk 4

**Rules:**
- `time_start = NULL` AND `time_end = NULL` → route is always active (no time restriction)
- Time windows are **non-overlapping** per prefix+priority (enforced by validation in Laravel)
- `days_of_week` allows weekday/weekend differentiation (e.g. cheaper weekend routes)
- A `NULL`-time route at a higher priority number serves as a universal failover

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
├── type ENUM('admin','reseller') DEFAULT 'admin'   -- who owns this rate group
├── parent_rate_group_id INT UNSIGNED NULL  -- for reseller: reference to admin's base rate group
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
├── effective_date DATE NOT NULL DEFAULT CURRENT_DATE  -- rate becomes active on this date
├── end_date DATE NULL              -- rate expires on this date (NULL = no expiry)
├── status ENUM('active','disabled')
├── created_at, updated_at
├── INDEX idx_prefix_date (rate_group_id, prefix, effective_date)
├── INDEX idx_effective (effective_date, end_date)

-- Rate import history (tracks every CSV import)
rate_imports
├── id (PK)
├── rate_group_id (FK→rate_groups.id)
├── uploaded_by (FK→users.id)       -- admin or reseller who uploaded
├── file_name VARCHAR(255)          -- original CSV filename
├── file_path VARCHAR(500)          -- stored CSV for audit
├── total_rows INT UNSIGNED DEFAULT 0
├── imported_rows INT UNSIGNED DEFAULT 0
├── skipped_rows INT UNSIGNED DEFAULT 0
├── error_rows INT UNSIGNED DEFAULT 0
├── error_log JSON NULL             -- [{row: 5, error: "invalid prefix"}, ...]
├── effective_date DATE NULL        -- apply rates from this date (bulk override)
├── status ENUM('pending','processing','completed','failed') DEFAULT 'pending'
├── completed_at TIMESTAMP NULL
├── created_at, updated_at
```

**Rate Effective Date logic:**
- Each rate has `effective_date` (when it becomes active) and `end_date` (when it expires)
- Rating engine picks the rate valid at call time: `effective_date <= call_date AND (end_date IS NULL OR end_date > call_date)`
- Upload future rates today (e.g., `effective_date = '2026-03-01'`) — they auto-activate on that date
- Old rates with `end_date` set auto-expire, no manual cleanup needed

**Rate Import/Export (CSV):**
```
┌─────────────────────────────────────────────────────────────────┐
│                     RATE IMPORT/EXPORT                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  IMPORT (CSV upload):                                             │
│  1. Admin/Reseller uploads CSV file                               │
│  2. Column mapping: prefix, destination, rate_per_minute,         │
│     connection_fee, min_duration, billing_increment, effective_date│
│  3. Preview first 20 rows with validation results                 │
│  4. Validation rules:                                             │
│     - prefix: required, numeric, 1-20 chars                      │
│     - rate_per_minute: required, numeric, > 0                    │
│     - destination: required, string                               │
│     - duplicate prefix detection (within same rate_group)        │
│  5. Import modes:                                                 │
│     - MERGE: add new prefixes, update existing (by prefix match) │
│     - REPLACE: delete all existing rates in group, insert new    │
│     - ADD_ONLY: skip existing prefixes, only add new             │
│  6. Background job (ImportRatesJob) for large files (>1000 rows) │
│  7. Import log stored in rate_imports table for audit             │
│  8. Error report downloadable as CSV                             │
│                                                                   │
│  EXPORT (CSV download):                                           │
│  1. Export entire rate group as CSV                               │
│  2. Export filtered rates (by prefix range, destination, status)  │
│  3. Columns: prefix, destination, rate_per_minute, connection_fee,│
│     min_duration, billing_increment, effective_date, status       │
│  4. Used for: rate comparison, provider negotiation, backup       │
│                                                                   │
│  CSV format example:                                              │
│  prefix,destination,rate_per_minute,connection_fee,effective_date │
│  1,USA,0.0100,0,2026-03-01                                      │
│  1212,USA New York,0.0085,0,2026-03-01                           │
│  44,UK Fixed,0.0120,0,2026-03-01                                 │
│  447,UK Mobile,0.0250,0,2026-03-01                               │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

**Reseller Rate Markup Model:**
```
┌─────────────────────────────────────────────────────────────────┐
│                   RESELLER RATE MARKUP MODEL                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  Admin rate group (base rates — admin's sell rate to resellers):  │
│  ┌──────────┬─────────────────┬──────────────────┐               │
│  │ prefix   │ destination     │ rate_per_minute   │               │
│  │ 1        │ USA             │ $0.0100           │               │
│  │ 44       │ UK Fixed        │ $0.0120           │               │
│  │ 880      │ Bangladesh      │ $0.0200           │               │
│  └──────────┴─────────────────┴──────────────────┘               │
│                                                                   │
│  Reseller creates own rate group (linked via parent_rate_group_id)│
│  Reseller's rate = admin's rate + reseller's markup               │
│  ┌──────────┬─────────────────┬──────────────────┐               │
│  │ prefix   │ destination     │ rate_per_minute   │               │
│  │ 1        │ USA             │ $0.0150 (+50%)    │               │
│  │ 44       │ UK Fixed        │ $0.0180 (+50%)    │               │
│  │ 880      │ Bangladesh      │ $0.0250 (+25%)    │               │
│  └──────────┴─────────────────┴──────────────────┘               │
│                                                                   │
│  Enforcement rules:                                               │
│  ├── Reseller rate MUST be >= admin's rate for same prefix        │
│  ├── Laravel validates on save: if reseller_rate < admin_rate     │
│  │   → reject with error "Rate below minimum"                    │
│  ├── Reseller can import CSV to set rates in bulk                │
│  ├── Reseller can ONLY create rates for prefixes that exist in   │
│  │   the admin's parent rate group                               │
│  └── If admin updates base rate above reseller's rate →           │
│      flag as "margin_warning" for reseller to fix                │
│                                                                   │
│  Billing chain at call time:                                      │
│  ├── Client charged at: reseller's rate (client's rate_group)    │
│  ├── Reseller charged at: admin's rate (reseller's rate_group)   │
│  ├── Profit: reseller's rate - admin's rate                      │
│  ├── call_records.total_cost = client charge                     │
│  └── call_records.reseller_cost = admin's charge to reseller     │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

### CDR & Billing (High Volume — 10M rows/day)

**Architecture change**: The old plan had two tables (`cdr` + `rated_cdr`). At 10M rows/day
that's 20M inserts/day and 3.65 billion rated rows/year. Instead we use a **single unified
`call_records` table** with **monthly partitioning** + **summary tables** for fast reporting.

**Why single table?**
- Eliminates double-write overhead (no raw cdr + rated_cdr)
- No JOIN between cdr↔rated_cdr for queries
- AGI writes call_start record, hangup handler UPDATEs with duration + cost
- One table to partition, index, and archive

**Billing flow (real-time, not batch):**
```
┌─ CALL START ──────────────────────────────────────────────┐
│ AGI runs → checks balance → finds rate → selects trunk    │
│ AGI INSERTs call_records row (status='in_progress')       │
│ AGI caches call_id + rate info in Redis (key: channel_id) │
│ Call proceeds...                                          │
├─ CALL END ────────────────────────────────────────────────┤
│ Asterisk hangup handler → triggers AGI or AMI event       │
│ Laravel reads Redis cache → gets rate, call_record_id     │
│ Calculates actual cost from billsec                       │
│ UPDATEs call_records row (duration, cost, status='rated') │
│ BalanceService::debit() → atomic balance deduction        │
│ Deletes Redis cache key                                   │
└───────────────────────────────────────────────────────────┘
```
**No batch CDR processor needed** — billing happens in real-time at call end.

```sql
-- Single unified CDR + billing table (replaces both cdr and rated_cdr)
call_records
├── id (PK, BIGINT AUTO_INCREMENT)
├── uuid VARCHAR(36) UNIQUE          -- Asterisk uniqueid for dedup
├── -- Call identification
├── sip_account_id INT UNSIGNED NULL -- NULL for Flow 4 (trunk→trunk)
├── user_id INT UNSIGNED             -- billed client (or DID owner for Flow 4)
├── reseller_id INT UNSIGNED NULL    -- reseller (for commission tracking)
├── call_flow ENUM('sip_to_trunk','sip_to_sip','trunk_to_sip','trunk_to_trunk')
├── -- Call details
├── caller VARCHAR(40)               -- calling number
├── callee VARCHAR(40)               -- called number
├── caller_id VARCHAR(80) NULL       -- CLID display
├── -- Network info
├── src_ip VARCHAR(45) NULL           -- source IP of the caller (SIP account or trunk)
├── dst_ip VARCHAR(45) NULL           -- destination IP (trunk or SIP account)
├── -- Routing info
├── incoming_trunk_id INT UNSIGNED NULL
├── outgoing_trunk_id INT UNSIGNED NULL
├── did_id INT UNSIGNED NULL
├── -- Rating info (set at call start by AGI)
├── destination VARCHAR(100)         -- matched destination name
├── matched_prefix VARCHAR(20)
├── rate_per_minute DECIMAL(10,6)
├── connection_fee DECIMAL(10,6) DEFAULT 0
├── rate_group_id INT UNSIGNED NULL
├── -- Duration & cost (updated at call end)
├── call_start DATETIME NOT NULL     -- partition key
├── call_end DATETIME NULL
├── duration INT UNSIGNED DEFAULT 0  -- total seconds
├── billsec INT UNSIGNED DEFAULT 0   -- billable seconds
├── billable_duration INT UNSIGNED DEFAULT 0  -- after increment rounding
├── total_cost DECIMAL(10,4) DEFAULT 0
├── reseller_cost DECIMAL(10,4) DEFAULT 0
├── -- Status
├── disposition ENUM('ANSWERED','NO ANSWER','BUSY','FAILED','CANCEL') NULL
├── hangup_cause VARCHAR(50) NULL
├── status ENUM('in_progress','rated','failed','unbillable') DEFAULT 'in_progress'
├── -- Asterisk raw fields (for debugging/audit)
├── ast_channel VARCHAR(80) NULL
├── ast_dstchannel VARCHAR(80) NULL
├── ast_context VARCHAR(40) NULL
├── -- Timestamps
├── rated_at TIMESTAMP NULL
├── created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
├──
├── -- PARTITIONED BY RANGE on call_start (monthly)
├── PRIMARY KEY (id, call_start)     -- partition key must be in PK
├── INDEX idx_uuid (uuid)
├── INDEX idx_user_date (user_id, call_start)
├── INDEX idx_reseller_date (reseller_id, call_start)
├── INDEX idx_status (status, call_start)
├── INDEX idx_sip_account (sip_account_id, call_start)
├── INDEX idx_caller (caller, call_start)
├── INDEX idx_callee (callee, call_start)
)
PARTITION BY RANGE (TO_DAYS(call_start)) (
  PARTITION p2026_01 VALUES LESS THAN (TO_DAYS('2026-02-01')),
  PARTITION p2026_02 VALUES LESS THAN (TO_DAYS('2026-03-01')),
  PARTITION p2026_03 VALUES LESS THAN (TO_DAYS('2026-04-01')),
  ...  -- auto-created by scheduled Laravel command
  PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

### CDR Summary Tables (Pre-aggregated for Dashboards)

Dashboards should **never** query `call_records` directly for stats.

### Real-Time Stats Architecture (Redis + MySQL)

```
┌───────────────────────────────────────────────────────────────────┐
│                    TWO-LAYER SUMMARY SYSTEM                       │
├───────────────────────────────────────────────────────────────────┤
│                                                                   │
│  LAYER 1: Redis (REAL-TIME) ← dashboards read from here          │
│  ├── Updated atomically by hangup handler AGI on every call end   │
│  ├── INCRBY/INCRBYFLOAT — near-zero overhead (sub-ms)             │
│  ├── Dashboards get instant stats (no delay)                      │
│  └── Keys auto-expire after 48 hours (Redis manages memory)       │
│                                                                   │
│  LAYER 2: MySQL (PERSISTENT) ← for reports, invoicing, archival  │
│  ├── Synced from Redis every 5 minutes by scheduled job           │
│  ├── Used for: invoice generation, CSV export, historical reports │
│  └── Retained forever (summary tables are small)                  │
│                                                                   │
│  Flow:                                                            │
│  Call ends → Hangup AGI → Redis INCRBY (real-time)                │
│                         → MySQL summary sync every 5 min          │
│  Dashboard → reads Redis → instant stats                          │
│  Invoice   → reads MySQL summary → accurate historical data       │
│                                                                   │
└───────────────────────────────────────────────────────────────────┘
```

### Layer 1: Redis Real-Time Counters

Updated atomically by the hangup handler AGI on **every call end** using Redis pipeline:

```
On each call end, hangup handler runs these Redis commands (pipelined, ~0.1ms):

// Per-user hourly counters
HINCRBY  stats:user:{user_id}:2026020414  total_calls 1
HINCRBY  stats:user:{user_id}:2026020414  answered_calls 1
HINCRBY  stats:user:{user_id}:2026020414  total_duration {duration}
HINCRBY  stats:user:{user_id}:2026020414  total_billable {billable}
HINCRBYFLOAT stats:user:{user_id}:2026020414  total_cost {cost}
EXPIRE   stats:user:{user_id}:2026020414  172800   // 48hr TTL

// Per-reseller hourly counters (if reseller exists)
HINCRBY  stats:reseller:{reseller_id}:2026020414  total_calls 1
...

// Per-trunk hourly counters
HINCRBY  stats:trunk:{trunk_id}:2026020414  total_calls 1
HINCRBY  stats:trunk:{trunk_id}:2026020414  answered_calls 1
HINCRBY  stats:trunk:{trunk_id}:2026020414  total_duration {duration}
EXPIRE   stats:trunk:{trunk_id}:2026020414  172800

// Per-destination daily counters
HINCRBY  stats:dest:{prefix}:20260204  total_calls 1
HINCRBY  stats:dest:{prefix}:20260204  answered_calls 1
HINCRBY  stats:dest:{prefix}:20260204  total_duration {duration}
HINCRBYFLOAT stats:dest:{prefix}:20260204  total_cost {cost}
EXPIRE   stats:dest:{prefix}:20260204  172800

// System-wide live counter (for admin dashboard "today" widget)
HINCRBY  stats:system:20260204  total_calls 1
HINCRBY  stats:system:20260204  answered_calls 1
HINCRBY  stats:system:20260204  total_duration {duration}
HINCRBYFLOAT stats:system:20260204  total_cost {cost}
HINCRBYFLOAT stats:system:20260204  total_revenue {cost}
EXPIRE   stats:system:20260204  172800

// Active channels gauge (INCR on call start, DECR on call end)
INCR  stats:active_channels              // on call start
DECR  stats:active_channels              // on call end (hangup)
INCR  stats:active_channels:{trunk_id}   // per-trunk
DECR  stats:active_channels:{trunk_id}
```

**Key format**: `stats:{scope}:{id}:{time_bucket}`
- User hourly: `stats:user:100:2026020414` (user 100, hour 14 of Feb 4)
- Trunk hourly: `stats:trunk:5:2026020414`
- Destination daily: `stats:dest:88017:20260204`
- System daily: `stats:system:20260204`

**Dashboard reads** (all sub-millisecond):
```
// Admin dashboard "Today" widget
HGETALL stats:system:20260204
GET stats:active_channels

// Client "Today" stats
HGETALL stats:user:100:2026020414   // current hour
HGETALL stats:user:100:2026020413   // previous hour
... aggregate last 24 hours for "today" view

// Reseller "Today" stats — aggregate all client keys
HGETALL stats:reseller:50:2026020414

// Trunk utilization
GET stats:active_channels:5
HGETALL stats:trunk:5:2026020414

// ASR calculation
asr = (answered_calls / total_calls) * 100  // computed client-side
acd = total_duration / answered_calls        // computed client-side
```

**Memory usage**: ~200 bytes per Redis hash × 24 hours × (users + trunks + prefixes) ≈ **< 500 MB** for entire system.

### Layer 2: MySQL Summary Tables (Persistent)

Synced from Redis every 5 minutes by `SyncCdrSummaryCommand`. Also used for historical queries beyond 48 hours.

```sql
-- Hourly summary per user (synced from Redis, used for reports/invoicing)
cdr_summary_hourly
├── id (PK, BIGINT)
├── user_id INT UNSIGNED
├── reseller_id INT UNSIGNED NULL
├── hour_start DATETIME              -- e.g. '2026-02-04 14:00:00'
├── total_calls INT UNSIGNED DEFAULT 0
├── answered_calls INT UNSIGNED DEFAULT 0
├── failed_calls INT UNSIGNED DEFAULT 0
├── total_duration INT UNSIGNED DEFAULT 0     -- seconds
├── total_billable INT UNSIGNED DEFAULT 0
├── total_cost DECIMAL(12,4) DEFAULT 0
├── total_reseller_cost DECIMAL(12,4) DEFAULT 0
├── asr DECIMAL(5,2) NULL            -- Answer Seizure Ratio %
├── acd DECIMAL(8,2) NULL            -- Average Call Duration (seconds)
├── updated_at TIMESTAMP
├── UNIQUE idx_user_hour (user_id, hour_start)
├── INDEX idx_reseller_hour (reseller_id, hour_start)

-- Daily summary per user (for invoicing, monthly reports)
cdr_summary_daily
├── id (PK, BIGINT)
├── user_id INT UNSIGNED
├── reseller_id INT UNSIGNED NULL
├── date DATE
├── total_calls INT UNSIGNED DEFAULT 0
├── answered_calls INT UNSIGNED DEFAULT 0
├── total_duration INT UNSIGNED DEFAULT 0
├── total_billable INT UNSIGNED DEFAULT 0
├── total_cost DECIMAL(12,4) DEFAULT 0
├── total_reseller_cost DECIMAL(12,4) DEFAULT 0
├── asr DECIMAL(5,2) NULL
├── acd DECIMAL(8,2) NULL
├── updated_at TIMESTAMP
├── UNIQUE idx_user_date (user_id, date)
├── INDEX idx_reseller_date (reseller_id, date)

-- Daily summary per destination prefix (for rate/trunk analysis)
cdr_summary_destination
├── id (PK, BIGINT)
├── date DATE
├── matched_prefix VARCHAR(20)
├── destination VARCHAR(100)
├── outgoing_trunk_id INT UNSIGNED NULL
├── total_calls INT UNSIGNED DEFAULT 0
├── answered_calls INT UNSIGNED DEFAULT 0
├── total_duration INT UNSIGNED DEFAULT 0
├── total_cost DECIMAL(12,4) DEFAULT 0
├── asr DECIMAL(5,2) NULL
├── acd DECIMAL(8,2) NULL
├── updated_at TIMESTAMP
├── UNIQUE idx_date_prefix_trunk (date, matched_prefix, outgoing_trunk_id)
```

**Sync job** (`SyncCdrSummaryCommand`, every 5 min):
```
1. SCAN Redis for stats:user:*:{current_hour} keys
2. For each key: HGETALL → UPSERT into cdr_summary_hourly
3. SCAN Redis for stats:dest:*:{today} keys
4. For each key: HGETALL → UPSERT into cdr_summary_destination
5. Roll up hourly → daily in cdr_summary_daily
```

### CDR Archival & Retention Strategy

```
┌──────────────────────────────────────────────────────────────┐
│                   DATA LIFECYCLE (12 months)                  │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  0-3 months:   HOT — MySQL (SSD), full indexes, fast queries│
│  3-6 months:   WARM — MySQL, reduce indexes (drop caller/   │
│                callee indexes on old partitions)             │
│  6-12 months:  COOL — MySQL, read-mostly, compressed tables │
│  12+ months:   COLD — Export partition to CSV/Parquet,       │
│                DROP partition from MySQL, store in S3/disk   │
│                Summary tables retained forever               │
│                                                              │
└──────────────────────────────────────────────────────────────┘

Automated by Laravel scheduled commands:
- CreateCdrPartitionCommand  — runs monthly, creates next 2 months partitions
- ArchiveCdrPartitionCommand — runs monthly, exports & drops partitions > 12 months
- CompressCdrPartitionCommand — runs monthly, compresses partitions 6-12 months old
- UpdateCdrSummaryCommand    — runs every 5 minutes, updates hourly/daily summaries
```

### Volume Calculations

```
10M rows/day assumptions:
├── Row size: ~400 bytes (optimized, no VARCHAR waste)
├── Daily data: 10M × 400B = ~3.8 GB/day
├── Monthly data: ~114 GB/month (1 partition)
├── Yearly data: ~1.37 TB (call_records only)
├── With indexes: ~2.5-3 TB/year total
├── Summary tables: ~5 GB/year (negligible)
│
├── Write throughput:
│   ├── Average: 10M/86400 = ~116 inserts/sec
│   ├── Peak (3x): ~350 inserts/sec + ~350 updates/sec
│   └── MySQL InnoDB handles this comfortably with partitioning
│
├── Query patterns:
│   ├── Dashboard stats → cdr_summary_hourly/daily (milliseconds)
│   ├── User CDR list → call_records with user_id + date range (fast, partition pruning)
│   ├── CDR export → call_records with date range (partition scan, async)
│   └── Invoice generation → cdr_summary_daily (instant)
│
└── Server recommendation for this volume:
    ├── CPU: 8+ cores
    ├── RAM: 32 GB (InnoDB buffer pool = 24 GB)
    ├── Storage: 4 TB NVMe SSD
    └── Separate MySQL server from Asterisk (recommended)
```

### Redis for Active Call State

```
During a call, Redis holds:
  Key:   call:{asterisk_channel_id}
  Value: {
    call_record_id: 12345678,
    user_id: 100,
    rate_per_minute: 0.015,
    connection_fee: 0,
    billing_increment: 6,
    min_duration: 0,
    rate_group_id: 5,
    call_start: "2026-02-04T14:30:00Z"
  }
  TTL: 7200 (2 hours, safety net)

On hangup → read this key → calculate cost → update call_records → debit balance → delete key
If Redis key missing on hangup → fallback: query call_records WHERE status='in_progress'
```

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

-- Payments (all recharge/payment events — online + manual)
payments
├── id (PK)
├── user_id (FK→users.id)            -- who is being recharged
├── -- Payment details
├── amount DECIMAL(12,4)              -- payment amount
├── currency VARCHAR(3) DEFAULT 'USD'
├── payment_method ENUM('online_stripe','online_paypal','online_sslcommerz',
│                        'bank_transfer','manual_admin','manual_reseller')
├── -- Online payment gateway fields
├── gateway_transaction_id VARCHAR(255) NULL  -- Stripe charge ID, PayPal txn, etc.
├── gateway_response JSON NULL        -- full gateway response for audit
├── -- Manual recharge fields
├── recharged_by (FK→users.id) NULL   -- admin or reseller who did manual recharge
├── notes VARCHAR(500) NULL           -- admin/reseller note (e.g. "Bank TT ref #123")
├── -- Status
├── status ENUM('pending','completed','failed','refunded') DEFAULT 'pending'
├── completed_at TIMESTAMP NULL
├── -- Links
├── transaction_id (FK→transactions.id) NULL  -- linked transaction after completion
├── invoice_id (FK→invoices.id) NULL  -- if paying a specific invoice
├── created_at, updated_at
├── INDEX idx_user_date (user_id, created_at)
├── INDEX idx_status (status)
├── INDEX idx_gateway_txn (gateway_transaction_id)

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
; /etc/asterisk/pjsip.conf
[global]
type=global
max_initial_qualify_time=4
keep_alive_interval=30
default_outbound_endpoint=         ; reject unknown traffic

[transport-udp]
type=transport
protocol=udp
bind=0.0.0.0:5060

[transport-tls]
type=transport
protocol=tls
bind=0.0.0.0:5061
cert_file=/etc/asterisk/keys/asterisk.pem
priv_key_file=/etc/asterisk/keys/asterisk.key
method=tlsv1_2

; SIP account endpoints are loaded dynamically from MySQL via Realtime
; All endpoints include NAT settings: rtp_symmetric=yes, force_rport=yes,
;   rewrite_contact=yes, direct_media=no (see Section 7.1)
; Trunk endpoints are generated by Laravel → pjsip_trunks.conf
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
 same => n,GotoIf($["${AGISTATUS}" = "FAILURE"]?agi_fail)

 same => n,GotoIf($["${ROUTE_TYPE}" = "local"]?local)
 same => n,GotoIf($["${ROUTE_TYPE}" = "trunk"]?trunk)
 same => n,Goto(denied)

 ; --- FLOW 2: SIP Account → SIP Account (local) ---
 same => n(local),NoOp(Local call to ${DEST_ENDPOINT})
 same => n,Dial(PJSIP/${DEST_ENDPOINT},30,Tt)
 same => n,Goto(hangup)

 ; --- FLOW 1: SIP Account → Outbound Trunk (PSTN) ---
 same => n(trunk),NoOp(Trunk call via ${TRUNK_1} to ${DIAL_NUM_1})
 same => n,Set(CDR(userfield)=${RATE_ID})
 same => n,Set(TIMEOUT(absolute)=${MAX_DURATION})
 ; Apply CLI manipulation from AGI
 same => n,Set(CALLERID(num)=${CLI_NUM})
 same => n,Set(CALLERID(name)=${CLI_NAME})
 ; Primary trunk (DIAL_NUM_1 already formatted by AGI for this trunk)
 same => n,Dial(PJSIP/${DIAL_NUM_1}@${TRUNK_1},60,Tt)
 same => n,NoOp(Primary trunk result: ${DIALSTATUS})
 same => n,GotoIf($["${DIALSTATUS}" = "ANSWER"]?done)
 ; Failover trunk (DIAL_NUM_2 formatted for failover trunk)
 same => n,GotoIf($["${TRUNK_2}" = ""]?hangup)
 same => n,NoOp(Failover to ${TRUNK_2})
 same => n,Dial(PJSIP/${DIAL_NUM_2}@${TRUNK_2},60,Tt)
 same => n,Goto(done)

 ; --- Denied (no balance / no route) ---
 same => n(denied),Playback(ss-noservice)
 same => n,Goto(hangup)

 ; --- AGI failure fallback ---
 same => n(agi_fail),NoOp(AGI FAILED — service unavailable)
 same => n,Playback(tt-allbusy)
 same => n,Hangup()

 same => n(done),Hangup()
 same => n(hangup),Hangup()

; --- Hangup handler: real-time billing at call end ---
exten => h,1,NoOp(== Hangup handler: billing for ${CHANNEL(endpoint)} ==)
 same => n,AGI(agi://127.0.0.1:4573,handle_hangup,${CHANNEL(uniqueid)},${CDR(billsec)},${DIALSTATUS})


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
 same => n,GotoIf($["${AGISTATUS}" = "FAILURE"]?agi_fail)

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
 same => n,Goto(hangup)

 ; --- AGI failure fallback ---
 same => n(agi_fail),NoOp(AGI FAILED — service unavailable for inbound)
 same => n,Playback(tt-allbusy)

 same => n(hangup),Hangup()

; --- Hangup handler: real-time billing for inbound calls ---
exten => h,1,NoOp(== Hangup handler: billing for trunk inbound ==)
 same => n,AGI(agi://127.0.0.1:4573,handle_hangup,${CHANNEL(uniqueid)},${CDR(billsec)},${DIALSTATUS})
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

Step 3 — External call: security checks
  → Check destination against destination_blacklist (prefix match)
    If BLACKLISTED → Set ROUTE_TYPE = "denied", log fraud attempt, RETURN
  → If user has destination_whitelist_enabled = true:
    Check destination against destination_whitelist for this user
    If NOT WHITELISTED → Set ROUTE_TYPE = "denied", RETURN
  → Check concurrent call count (Redis stats:active_channels:{user_id})
    If >= user.max_channels → Set ROUTE_TYPE = "denied", RETURN
  → Check daily spend (Redis stats:user:{user_id}:{today} total_cost)
    If >= user.daily_spend_limit → Set ROUTE_TYPE = "denied",
    auto-suspend user, alert admin, RETURN

Step 4 — External call: rate lookup (with effective date)
  → Longest prefix match on rates table using caller's rate_group_id
    WHERE effective_date <= CURDATE()
      AND (end_date IS NULL OR end_date > CURDATE())
  → If NO rate found → Set ROUTE_TYPE = "denied", RETURN
  → Also lookup reseller's rate (if user has reseller) for reseller_cost calculation

Step 5 — External call: balance check (with minimum threshold)
  → Prepaid:  If balance < user.min_balance_for_calls → DENIED
              max_duration = ((balance - min_balance_for_calls) / rate_per_minute) * 60
  → Postpaid: remaining = credit_limit - outstanding_charges
              If remaining <= 0 → DENIED
  → Set MAX_DURATION (seconds)

Step 6 — External call: select outgoing trunk (prefix + time-based)
  → Get current time in route's timezone
  → Longest prefix match + time window filter on trunk_routes table:
    SELECT t.*, tr.prefix, tr.priority, tr.weight,
           tr.time_start, tr.time_end
    FROM trunk_routes tr
    JOIN trunks t ON t.id = tr.trunk_id
    WHERE ${destination} LIKE CONCAT(tr.prefix, '%')
      AND tr.status = 'active'
      AND t.status = 'active'
      AND t.direction IN ('outgoing','both')
      AND (
        -- No time restriction (always active)
        (tr.time_start IS NULL AND tr.time_end IS NULL)
        OR
        -- Current time falls within the time window
        (CONVERT_TZ(NOW(), 'UTC', tr.timezone) BETWEEN tr.time_start AND tr.time_end)
        OR
        -- Overnight window (e.g. 22:00 → 06:00)
        (tr.time_start > tr.time_end
         AND (CONVERT_TZ(NOW(), 'UTC', tr.timezone) >= tr.time_start
              OR CONVERT_TZ(NOW(), 'UTC', tr.timezone) < tr.time_end))
      )
      AND (
        -- No day restriction
        tr.days_of_week IS NULL
        OR
        -- Current day matches
        FIND_IN_SET(LOWER(DAYNAME(CONVERT_TZ(NOW(), 'UTC', tr.timezone))),
                    tr.days_of_week)
      )
    ORDER BY LENGTH(tr.prefix) DESC, tr.priority ASC, tr.weight DESC
  → Check trunk capacity (active channels < max_channels via AMI)
  → Filter out trunks with health_status = 'down' or status = 'auto_disabled'
  → Pick TRUNK_1 (primary, lowest priority number in time window)
  → Pick TRUNK_2 (failover, next priority or NULL-time fallback route)
  → If NO trunk found → DENIED

Step 6b — Apply dial string manipulation (per trunk)
  → For TRUNK_1: apply dial_strip_digits → dial_pattern_match/replace → dial_prefix → tech_prefix
  → For TRUNK_2: same manipulation with TRUNK_2's settings
  → Result: DIAL_NUM_1 and DIAL_NUM_2 (formatted for each trunk's expected format)

Step 6c — Apply Caller ID / CLI manipulation (per trunk)
  → Read trunk.cli_mode for TRUNK_1:
    passthrough: use SIP account's caller_id_number as-is
    override:    set CALLERID(num) = trunk.cli_override_number
    prefix_strip: strip N digits from left, prepend cli_prefix_add
    translate:   strip + add (format conversion)
    hide:        set CALLERID(num)="anonymous", set Privacy header
  → Set CLI_NUM for use in Dial()

Step 7 — Create call_record + cache in Redis
  → INSERT call_records (status='in_progress', call_start=NOW(),
      user_id, sip_account_id, caller, callee, rate_per_minute,
      matched_prefix, destination, outgoing_trunk_id, call_flow='sip_to_trunk')
  → Cache in Redis: call:{channel_id} = {call_record_id, rate_per_minute,
      billing_increment, min_duration, connection_fee, user_id}
  → TTL = 7200 seconds (2hr safety net)

Step 8 — Return variables
  → ROUTE_TYPE   = "trunk"
  → TRUNK_1      = trunk endpoint name (e.g. "trunk-outgoing-1")
  → TRUNK_2      = failover trunk endpoint name (or empty)
  → DIAL_NUM_1   = formatted destination for TRUNK_1 (after dial manipulation)
  → DIAL_NUM_2   = formatted destination for TRUNK_2 (after dial manipulation)
  → CLI_NUM      = formatted Caller ID for outgoing trunk
  → CLI_NAME     = Caller ID name
  → MAX_DURATION = max call duration in seconds


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


═══════════════════════════════════════════════════════════════
AGI HANDLER 3: handle_hangup(channel_id, billsec, disposition)
Called from: hangup handler (h extension) in both contexts
Handles:     Real-time billing at call end for ALL flows
═══════════════════════════════════════════════════════════════

Step 1 — Read cached call state from Redis
  → GET call:{channel_id}
  → If NOT found → query call_records WHERE status='in_progress'
    AND ast_channel LIKE '%{channel_id}%' (fallback)
  → If still not found → log warning, RETURN (unbillable)

Step 2 — Calculate actual cost
  → billable_duration = apply min_duration + billing_increment rounding
    Example: billsec=37s, increment=6 → ceil(37/6)*6 = 42s billable
  → total_cost = (billable_duration / 60) * rate_per_minute + connection_fee
  → reseller_cost = same calc with reseller's rate (if applicable)

Step 3 — Update call_records
  → UPDATE call_records SET
      call_end = NOW(),
      duration = actual_duration,
      billsec = billsec,
      billable_duration = calculated,
      total_cost = calculated,
      reseller_cost = calculated,
      disposition = disposition,
      status = 'rated',
      rated_at = NOW()
    WHERE id = call_record_id

Step 4 — Debit balance (atomic)
  → BalanceService::debit(user_id, total_cost)
  → Creates Transaction record
  → If reseller: BalanceService::debit(reseller_id, reseller_cost)

Step 5 — Cleanup
  → DEL call:{channel_id} from Redis
  → If balance after debit < low_threshold → queue LowBalanceNotification
```

### CDR Collection — Real-Time (No cdr_adaptive_odbc)

**We do NOT use `cdr_adaptive_odbc`** for high-volume operation. Instead:

1. **Call start**: AGI `route_outbound`/`route_inbound` INSERTs `call_records` row directly
2. **During call**: Rate info cached in Redis (key: `call:{channel_id}`)
3. **Call end**: Hangup handler AGI reads Redis → calculates cost → UPDATEs `call_records` → debits balance
4. **Stalled calls**: `RecoverStalledCallsCommand` runs hourly, finds `in_progress` records > 2 hours old, marks as `failed`

This eliminates the need for batch CDR processing entirely. Billing is real-time.

```ini
; Disable cdr_adaptive_odbc in modules.conf to avoid double-writing
; /etc/asterisk/modules.conf
[modules]
noload => cdr_adaptive_odbc.so
; We handle CDR in application layer via AGI
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
│   │   │   │   ├── KycController.php          -- review, approve, reject KYC
│   │   │   │   ├── ResellerController.php
│   │   │   │   ├── TrunkController.php       -- incoming, outgoing, both
│   │   │   │   ├── TrunkRouteController.php  -- prefix→trunk routing rules
│   │   │   │   ├── DidController.php
│   │   │   │   ├── RateGroupController.php
│   │   │   │   ├── RateImportController.php     -- CSV upload, preview, import rates
│   │   │   │   ├── TrunkHealthController.php    -- view trunk health, manual check
│   │   │   │   ├── TransferController.php         -- transfer SIP accounts & clients
│   │   │   │   ├── TransferLogController.php     -- view transfer audit history
│   │   │   │   ├── ManualRechargeController.php  -- admin recharges any user
│   │   │   │   ├── PaymentHistoryController.php  -- view all payments system-wide
│   │   │   │   ├── DestinationBlacklistController.php  -- manage fraud blacklist
│   │   │   │   ├── AuditLogController.php        -- view audit trail
│   │   │   │   └── SystemSettingController.php
│   │   │   ├── Reseller/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── ClientController.php
│   │   │   │   ├── SipAccountController.php
│   │   │   │   ├── DidController.php
│   │   │   │   ├── RateGroupController.php
│   │   │   │   ├── RateImportController.php     -- CSV import for own rate groups
│   │   │   │   └── ClientRechargeController.php  -- reseller recharges own clients
│   │   │   ├── Client/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── SipAccountController.php
│   │   │   │   └── CdrController.php
│   │   │   ├── Common/
│   │   │   │   ├── KycProfileController.php   -- submit/update KYC form + upload docs
│   │   │   │   ├── OnlineRechargeController.php -- self-service online payment (Stripe/PayPal)
│   │   │   │   ├── PaymentHistoryController.php -- view own payment history
│   │   │   │   ├── CdrController.php
│   │   │   │   ├── InvoiceController.php
│   │   │   │   ├── TransactionController.php
│   │   │   │   └── ProfileController.php
│   │   │   └── Auth/
│   │   ├── Middleware/
│   │   │   ├── RoleMiddleware.php
│   │   │   ├── KycApprovedMiddleware.php  -- blocks access if KYC not approved
│   │   │   ├── TwoFactorMiddleware.php   -- enforce 2FA for admin accounts
│   │   │   └── TenantScope.php       -- auto-filter by parent hierarchy
│   │   └── Requests/
│   ├── Models/
│   │   ├── User.php
│   │   ├── KycProfile.php
│   │   ├── KycDocument.php
│   │   ├── SipAccount.php
│   │   ├── Trunk.php
│   │   ├── TrunkRoute.php
│   │   ├── Did.php
│   │   ├── RateGroup.php
│   │   ├── Rate.php
│   │   ├── CallRecord.php
│   │   ├── CdrSummaryHourly.php
│   │   ├── CdrSummaryDaily.php
│   │   ├── Payment.php
│   │   ├── Transaction.php
│   │   ├── Invoice.php
│   │   ├── TransferLog.php
│   │   ├── AuditLog.php
│   │   ├── RateImport.php
│   │   ├── DestinationBlacklist.php
│   │   ├── DestinationWhitelist.php
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
│   │   │   ├── RatingEngine.php       -- Longest prefix match + effective_date, cost calc
│   │   │   ├── BalanceService.php     -- Credit/debit operations (atomic)
│   │   │   ├── CallRecordService.php   -- Insert at call start, update at call end
│   │   │   ├── InvoiceGenerator.php   -- Monthly invoice creation
│   │   │   └── PaymentService.php     -- Online payment + manual recharge logic
│   │   ├── Rate/
│   │   │   ├── RateImportService.php  -- CSV parse, validate, preview, import
│   │   │   └── RateExportService.php  -- CSV/Excel export of rate groups
│   │   ├── Trunk/
│   │   │   ├── DialStringService.php  -- Per-trunk number format manipulation
│   │   │   ├── CliManipulationService.php -- Per-trunk Caller ID rules
│   │   │   └── TrunkHealthService.php -- SIP OPTIONS ping, ASR monitoring
│   │   ├── PaymentGateway/
│   │   │   ├── PaymentGatewayInterface.php  -- common interface
│   │   │   ├── StripeGateway.php      -- Stripe Checkout / Payment Intent
│   │   │   ├── PaypalGateway.php      -- PayPal integration
│   │   │   └── SslcommerzGateway.php  -- SSLCommerz (BD local gateway)
│   │   ├── Kyc/
│   │   │   └── KycService.php         -- submit, validate docs, approve/reject
│   │   ├── Transfer/
│   │   │   └── TransferService.php    -- transfer SIP accounts & clients between owners
│   │   └── Did/
│   │       └── DidService.php
│   ├── Notifications/
│   │   ├── KycSubmittedNotification.php   -- notify admin when KYC submitted
│   │   ├── KycApprovedNotification.php    -- notify user when KYC approved
│   │   ├── KycRejectedNotification.php    -- notify user when KYC rejected (with reason)
│   │   ├── PaymentReceivedNotification.php   -- notify user on successful recharge
│   │   ├── PaymentFailedNotification.php     -- notify user on failed payment
│   │   ├── LowBalanceNotification.php        -- notify when balance below threshold
│   │   ├── FraudAlertNotification.php        -- alert admin on suspicious activity
│   │   ├── AccountLockedNotification.php     -- notify on failed login lockout
│   │   ├── TrunkDownNotification.php         -- alert admin when trunk fails health check
│   │   ├── TrunkRecoveredNotification.php    -- notify admin when trunk comes back up
│   │   └── TrunkDegradedNotification.php     -- alert admin when trunk ASR drops
│   ├── Jobs/
│   │   ├── SyncCdrSummaryJob.php      -- Every 5 min: Redis counters → MySQL summary tables
│   │   ├── RateHangupJob.php          -- Queued: rate + debit on call end (async fallback)
│   │   ├── ImportRatesJob.php         -- Background: CSV rate import (large files)
│   │   ├── GenerateInvoicesJob.php    -- Monthly
│   │   ├── SuspendOverdueJob.php      -- Check postpaid limits
│   │   └── ProvisionEndpointJob.php
│   ├── Scopes/
│   │   └── TenantScope.php           -- Global scope for hierarchy filtering
│   ├── Observers/
│   │   └── SipAccountObserver.php    -- Sync to Asterisk realtime on create/update/delete
│   └── Console/
│       └── Commands/
│           ├── SyncCdrSummaryCommand.php       -- every 5 min: Redis → MySQL summaries
│           ├── CreateCdrPartitionCommand.php   -- monthly: create next 2 partitions
│           ├── ArchiveCdrPartitionCommand.php  -- monthly: export + drop > 12 months
│           ├── CompressCdrPartitionCommand.php -- monthly: compress 6-12 month partitions
│           ├── RecoverStalledCallsCommand.php  -- hourly: find in_progress > 2hrs, mark failed
│           ├── GenerateInvoicesCommand.php
│           ├── TrunkHealthCheckCommand.php   -- every 60s: SIP OPTIONS ping + ASR check
│           └── AsteriskHealthCheckCommand.php
├── agi/
│   ├── server.php                     -- FastAGI daemon entry point
│   ├── handlers/
│   │   ├── RouteOutboundHandler.php   -- Flow 1 & 2: SIP→Trunk or SIP→SIP
│   │   ├── RouteInboundHandler.php    -- Flow 3 & 4: Trunk→SIP or Trunk→Trunk
│   │   └── HangupHandler.php         -- Real-time billing at call end (all flows)
│   └── bootstrap.php                  -- Laravel app bootstrap for AGI context
├── database/
│   └── migrations/
│       ├── 0001_create_users_table.php
│       ├── 0001b_create_kyc_profiles_table.php
│       ├── 0001c_create_kyc_documents_table.php
│       ├── 0002_create_sip_accounts_table.php
│       ├── 0003_create_trunks_table.php
│       ├── 0003b_create_trunk_routes_table.php
│       ├── 0004_create_dids_table.php
│       ├── 0005_create_rate_groups_table.php
│       ├── 0006_create_rates_table.php
│       ├── 0007_create_call_records_table.php  -- partitioned by month
│       ├── 0008_create_cdr_summary_tables.php -- hourly, daily, destination
│       ├── 0009_create_transactions_table.php
│       ├── 0009b_create_payments_table.php
│       ├── 0010_create_invoices_table.php
│       ├── 0011_create_asterisk_realtime_tables.php
│       ├── 0012_create_transfer_logs_table.php
│       ├── 0013_create_audit_logs_table.php
│       ├── 0014_create_destination_blacklist_table.php
│       ├── 0015_create_destination_whitelist_table.php
│       └── 0016_create_rate_imports_table.php
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

#### RatingEngine — Longest Prefix Match (with Effective Date)
```php
// Find rate: match longest prefix, valid at call time
SELECT * FROM rates
WHERE rate_group_id = ?
  AND ? LIKE CONCAT(prefix, '%')
  AND status = 'active'
  AND effective_date <= CURDATE()
  AND (end_date IS NULL OR end_date > CURDATE())
ORDER BY LENGTH(prefix) DESC, effective_date DESC
LIMIT 1;
// effective_date DESC ensures latest valid rate wins if overlapping dates
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

#### Payment & Recharge Flows

**3 ways to recharge an account:**

```
┌─────────────────────────────────────────────────────────────────┐
│                     RECHARGE METHODS                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. ONLINE SELF-RECHARGE (Reseller or Client)                   │
│     User → selects amount → chooses gateway (Stripe/PayPal/     │
│     SSLCommerz) → redirected to payment page → gateway callback │
│     → payment verified → balance credited → receipt emailed     │
│                                                                 │
│  2. ADMIN MANUAL RECHARGE                                       │
│     Admin → selects any user → enters amount + note →           │
│     balance credited instantly → transaction logged              │
│                                                                 │
│  3. RESELLER RECHARGES OWN CLIENT                               │
│     Reseller → selects own client → enters amount →             │
│     deducted from reseller balance → credited to client →       │
│     both transactions logged                                    │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

**Online payment flow (Stripe example):**
```php
// 1. User submits recharge form
PaymentService::initiateOnlinePayment($user, $amount, 'stripe');
  → Creates Payment record (status = 'pending')
  → Creates Stripe Checkout Session / Payment Intent
  → Returns redirect URL to Stripe

// 2. User completes payment on Stripe
// 3. Stripe webhook hits /api/webhooks/stripe
PaymentService::handleGatewayCallback($gatewayTxnId, $response);
  → Verifies payment with gateway
  → Updates Payment record (status = 'completed')
  → BalanceService::credit($user, $amount)
    → Atomic: updates user.balance + creates Transaction
  → Links Payment → Transaction
  → Sends PaymentReceivedNotification

// 4. If payment fails
  → Updates Payment record (status = 'failed')
  → Sends PaymentFailedNotification
```

**Reseller → Client recharge flow:**
```php
RechargeService::resellerRechargeClient($reseller, $client, $amount);
  → Validates: client.parent_id == reseller.id
  → Validates: reseller.balance >= amount
  → DB::transaction {
      BalanceService::debit($reseller, $amount)   // deduct from reseller
      BalanceService::credit($client, $amount)     // add to client
      Payment::create(method='manual_reseller', recharged_by=reseller.id)
    }
```

**Payment history — what each role sees:**

| Role | Sees |
|---|---|
| Admin | All payments system-wide, filter by user/method/status/date |
| Reseller | Own payments + own clients' payments |
| Client | Own payments only |

#### Admin Transfer Operations

Admin can transfer SIP accounts between clients, and transfer clients between resellers.

**Two transfer types:**

```
┌─────────────────────────────────────────────────────────────────┐
│                     ADMIN TRANSFERS                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. TRANSFER SIP ACCOUNT → Another Client                       │
│     Admin picks SIP account → selects target client →            │
│     sip_accounts.user_id updated → Asterisk realtime reloaded   │
│     CDR history stays linked to original user (audit trail)      │
│                                                                  │
│  2. TRANSFER CLIENT → Another Reseller                          │
│     Admin picks client → selects target reseller →               │
│     users.parent_id updated → all client's SIP accounts,        │
│     DIDs, and billing stay intact                               │
│     Future CDR/billing goes through new reseller                │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

**Transfer logic:**
```php
// 1. Transfer SIP account to another client
TransferService::transferSipAccount($sipAccount, $targetClient, $admin);
  → Validates: target client is active + KYC approved
  → Validates: SIP account is not in active call (check Redis call:* keys)
  → DB::transaction {
      $sipAccount->user_id = $targetClient->id;
      $sipAccount->save();
      // Asterisk realtime: endpoint stays the same, no PJSIP change needed
      TransferLog::create(type='sip_account', ...);
    }

// 2. Transfer client to another reseller
TransferService::transferClient($client, $targetReseller, $admin);
  → Validates: target reseller is active + KYC approved
  → Validates: no circular reference
  → DB::transaction {
      $client->parent_id = $targetReseller->id;
      $client->save();
      // Client's SIP accounts, DIDs, balance — all stay intact
      // Rate group optionally reassigned (admin chooses)
      TransferLog::create(type='client', ...);
    }
```

**Transfer audit log:**
```sql
transfer_logs
├── id (PK)
├── transfer_type ENUM('sip_account','client')
├── -- What was transferred
├── transferred_item_id INT UNSIGNED   -- sip_account.id or user.id (client)
├── transferred_item_type VARCHAR(30)  -- 'sip_account' or 'user'
├── -- From / To
├── from_parent_id INT UNSIGNED        -- old owner (client or reseller)
├── to_parent_id INT UNSIGNED          -- new owner (client or reseller)
├── -- Who did it
├── performed_by INT UNSIGNED          -- admin user.id
├── reason VARCHAR(500) NULL           -- admin's note for why
├── -- Snapshot at transfer time (for audit)
├── metadata JSON NULL                 -- balance, rate_group, active SIP accounts count, etc.
├── created_at TIMESTAMP
├── INDEX idx_item (transferred_item_type, transferred_item_id)
├── INDEX idx_date (created_at)
```

#### SipAccount Observer — Asterisk Sync
When a SipAccount is created/updated/deleted, the observer writes to `ps_endpoints`, `ps_auths`, and `ps_aors` tables, then optionally reloads PJSIP via AMI.

### Role-Based Access

Using **Spatie Laravel Permission** or custom middleware:

| Feature | Admin | Reseller | Client |
|---|---|---|---|
| **KYC** | | | |
| Submit KYC form | -- | Yes | Yes |
| Review/approve/reject KYC | Yes | No | No |
| **Requires KYC approved** | | | |
| Create clients | Yes | Yes (own) | No |
| Create SIP accounts | Yes | Yes (own clients) | Limited |
| Make calls / use balance | -- | -- | KYC required |
| Top-up balance | Any user | Own clients | No |
| **General** | | | |
| Manage resellers | Yes | No | No |
| Manage trunks (in/out/both) | Yes | No | No |
| Manage trunk routes | Yes | No | No |
| Manage all DIDs | Yes | No | No |
| View CDR | All | Own tree | Own |
| Manage rates | All groups | Own groups | No |
| Import/export rates (CSV) | All groups | Own groups | No |
| Trunk health monitoring | Yes | No | No |
| View invoices | All | Own tree | Own |
| Transfer SIP accounts | Yes | No | No |
| Transfer clients | Yes | No | No |
| View transfer logs | Yes | No | No |
| System settings | Yes | No | No |

**KYC gate**: The `KycApprovedMiddleware` is applied to all routes that require KYC. Until `kyc_status = 'approved'`, resellers and clients are redirected to the KYC form page. They can view their dashboard (with a KYC banner) and submit/edit KYC, but cannot access SIP accounts, billing, or CDR features.

---

## 4. Implementation Phases

### Phase 1: Foundation
- Laravel project setup (auth, roles, middleware)
- Database migrations (all tables)
- User CRUD with hierarchy (Admin creates Resellers, Resellers create Clients)
- Tenant scoping (users only see their own subtree)
- **KYC module**:
  - KYC form (personal/company info + document uploads)
  - File storage in `storage/app/kyc/{user_id}/` (private, not public)
  - KYC submission by resellers and clients
  - Admin KYC review panel (list pending, view documents, approve/reject with reason)
  - `KycApprovedMiddleware` — blocks feature access until KYC approved
  - Dashboard shows KYC status banner for non-approved users
- Basic UI layout with role-based navigation
- Asterisk installation and base configuration
- MySQL ODBC connector setup for Asterisk Realtime
- **Security (P0 — Day 1)**:
  - Fail2ban for Asterisk SIP brute force protection
  - Asterisk runs as non-root (`runuser = asterisk`)
  - Disable unused Asterisk modules (`autoload = no`, explicit module loading)
  - NAT settings on all SIP endpoints (rtp_symmetric, force_rport, rewrite_contact, direct_media=no)
  - Strong auto-generated SIP passwords (16+ chars)
  - iptables/nftables: block AMI, Redis, MySQL, FastAGI from public access
  - HTTPS for web panel (Let's Encrypt)
  - Login rate limiting (5 attempts/min per IP)
  - Security headers (CSP, HSTS, X-Frame-Options, X-Content-Type-Options)
  - Redis password + disabled dangerous commands (FLUSHALL, CONFIG)
  - Minimal MySQL privileges for Asterisk realtime user (SELECT-only)
  - AGI daemon managed by Supervisor with auto-restart
  - AGI failure fallback in dialplan (`AGISTATUS` check → play error)
  - Destination blacklist table (block known toll fraud prefixes)
  - Per-user concurrent call limits + daily spend limits
  - Audit logging on admin actions
- **Deliverable**: Login, user management with hierarchy, KYC workflow, Asterisk running, security hardened

### Phase 2: Core Switching
- **SIP Account CRUD** → auto-provision to Asterisk realtime tables (ps_endpoints/ps_auths/ps_aors)
  - Support all auth modes: password, IP-based, both
  - IP restriction (allowed_ips) with ps_endpoint_id_ips provisioning
  - Per-account max channels
- **Trunk management (incoming + outgoing + both)**:
  - Admin: add/edit/delete trunks with direction (incoming/outgoing/both)
  - Auto-generate `pjsip_trunks.conf` with endpoint, auth, AOR, identify, registration sections
  - Reload PJSIP via AMI after trunk changes
  - **Dial string manipulation** per trunk (strip, pattern match/replace, prefix, tech prefix)
  - **Caller ID / CLI manipulation** per trunk (passthrough, override, strip, translate, hide)
- **Trunk routing rules**: prefix-based outgoing trunk selection with priority/failover
- **Trunk health monitoring**:
  - SIP OPTIONS ping every 60 seconds (`TrunkHealthCheckCommand`)
  - Auto-disable trunk after N consecutive failures
  - ASR-based degradation alerts
  - Admin notifications on trunk down/recovered/degraded
- Basic dialplan:
  - Outbound: SIP account → AGI (security + rate + balance + trunk + CLI/dial manipulation) → Dial
  - Inbound: incoming trunk → AGI matches DID → routes to SIP account
  - Internal: SIP-to-SIP direct calls
- DID management and assignment (linked to incoming/both trunks)
- SIP registration monitoring via AMI (both SIP accounts and trunk registrations)
- `call_records` table with monthly partitioning + partition auto-creation command
- Redis caching for active call state
- Basic CDR viewer (call_records, paginated with partition pruning)
- **Security (P1 — before production)**:
  - SIP TLS transport (port 5061) for endpoints supporting it
  - 2FA (TOTP) mandatory for admin accounts
  - Account lockout after repeated failed logins
  - Trunk IP ACLs (acl.conf per trunk provider)
  - Destination whitelist option per client (restrict to specific prefixes)
- **Deliverable**: Working calls with all auth modes, trunk health monitoring, CLI/dial manipulation, ACLs active

### Phase 3: Billing Engine
- **Rate group and rate management** (prefix-based):
  - Rate groups with `effective_date` and `end_date` per rate
  - Schedule future rate changes (auto-activate on effective_date)
  - **CSV rate import**: upload, preview, validate, import (MERGE/REPLACE/ADD_ONLY modes)
  - **CSV rate export**: download rate group as CSV/Excel
  - Background `ImportRatesJob` for large files (>1000 rows)
  - Import audit trail (`rate_imports` table with error log)
- **Rating engine** (longest prefix match + effective date):
  - Picks rate valid at call time (`effective_date <= today AND (end_date IS NULL OR end_date > today)`)
  - Dual rate lookup: client rate + reseller rate (for commission tracking)
- **Real-time billing via hangup handler AGI** (no batch CDR processor):
  - AGI inserts `call_records` at call start, caches rate in Redis
  - Hangup handler AGI calculates cost, updates record, debits balance
  - `RecoverStalledCallsCommand` — hourly cleanup for orphaned in_progress records
- **Balance service** (atomic credit/debit with row locking):
  - Minimum balance threshold (`min_balance_for_calls`) checked before call
  - Configurable `low_balance_threshold` for notifications
- Prepaid: AGI balance check before call, max duration enforcement
- Postpaid: credit limit enforcement
- Transaction log
- Top-up functionality (admin/reseller adds credit to accounts)
- **Real-time Redis counters** — hangup handler INCRBY on every call end (per-user, per-trunk, per-destination, system-wide)
- **MySQL summary sync** — `SyncCdrSummaryCommand` every 5 min (Redis → MySQL for persistence)
- Active channel counters in Redis (INCR on start, DECR on end)
- **Deliverable**: Calls are rated in real-time, balances deducted instantly, rate import/export works, dashboard stats are live

### Phase 4: Business Features
- **Reseller rate markup model**:
  - Reseller rate group linked to admin's base rate group (`parent_rate_group_id`)
  - Reseller sets own rates — must be >= admin's rate (enforced by validation)
  - Reseller can import rates via CSV (own groups only)
  - Margin warning flag if admin raises base rate above reseller's sell rate
  - Billing chain: client charged at reseller rate, reseller charged at admin rate
- Reseller credit system (admin allocates credit to reseller, reseller allocates to clients)
- Invoice generation (monthly, for postpaid accounts)
- Invoice PDF export
- DID monthly billing (recurring charges)
- Account suspension on zero balance (prepaid) or over credit limit (postpaid)
- **Admin transfer operations**:
  - Transfer SIP accounts between clients (update ownership, reload Asterisk if needed)
  - Transfer clients between resellers (update parent_id, billing continues under new reseller)
  - Transfer audit log (`transfer_logs` table with metadata snapshots)
  - Active call check before SIP account transfer (via Redis `call:*` keys)
- **Deliverable**: Full reseller workflow, invoicing, automated suspension, admin transfers

### Phase 5: Dashboards & Monitoring
- Admin dashboard — **real-time from Redis**:
  - Today's calls/revenue/duration from `stats:system:{today}`
  - Active channels from `stats:active_channels`
  - Per-trunk utilization from `stats:trunk:{id}:{hour}`
  - All sub-millisecond reads, zero MySQL load
- Reseller dashboard — reads `stats:reseller:{id}:{hour}` from Redis
- Client dashboard — reads `stats:user:{id}:{hour}` from Redis
- Live channel monitor (active calls via Redis `call:*` keys + AMI)
- CDR export (CSV/Excel — async job, queries partitioned `call_records`)
- Call quality metrics (ASR, ACD from summary tables)
- **CDR data lifecycle management**:
  - Partition auto-creation (monthly)
  - Partition archival > 12 months (export to CSV/Parquet + DROP)
  - Partition compression for 6-12 month data
- Asterisk health check command
- **Deliverable**: Production-ready MVP with monitoring, scales to 10M+ calls/day

---

## 5. Key Technical Decisions

| Decision | Choice | Rationale |
|---|---|---|
| SIP driver | PJSIP (not chan_sip) | chan_sip is deprecated in Asterisk 21 |
| SIP provisioning | Asterisk Realtime (ARA) via MySQL | No config file rewrites; instant provisioning |
| Billing gate | FastAGI (PHP) | Direct access to Laravel's DB/services; low latency |
| CDR storage | Single `call_records` table (no cdr + rated_cdr split) | Eliminates double-write; one table to partition/query |
| CDR write method | AGI writes at call start + hangup handler updates | Real-time billing, no batch processing lag |
| CDR partitioning | Monthly RANGE partitioning on `call_start` | Partition pruning for fast queries; easy archival by dropping old partitions |
| CDR reporting | Redis real-time counters + MySQL summary sync | Dashboards read Redis (instant); MySQL persists for invoicing/history |
| Active call state | Redis (TTL 2hr) | Sub-millisecond reads for hangup handler; no DB query during billing |
| Balance tracking | MySQL row-level locking + Redis cache | Atomic operations; Redis for fast AGI reads |
| Auth/permissions | Spatie Laravel Permission | Battle-tested; supports role hierarchy |
| Frontend | Blade + Livewire (or Inertia + Vue) | Rapid development for MVP |
| Trunk direction | Single table with direction ENUM | Avoids duplicate schemas; one trunk can handle both directions |
| Outgoing trunk selection | Prefix + time-based routing with priority + failover | Longest prefix match + time window filter; AGI returns primary + backup trunk |
| Incoming trunk auth | IP-based (identify) or registration | Flexible per provider; most ITSP use IP-based |
| Trunk provisioning | Laravel generates pjsip_trunks.conf + AMI reload | Keeps trunk config in sync; no manual Asterisk editing |
| NAT handling | direct_media=no, rtp_symmetric, force_rport, rewrite_contact | Fixes one-way audio; keeps media through Asterisk for billing accuracy |
| SIP security | Fail2ban + IP ACLs + strong passwords + TLS | Multi-layer protection against brute force and toll fraud |
| Toll fraud prevention | Destination blacklist + spend limits + concurrent call limits | Prevents high-cost fraudulent calls; auto-suspend on anomaly |
| Web auth security | Rate limiting + 2FA (admin) + account lockout + audit log | Protects against brute force; full audit trail for compliance |
| AGI resilience | Supervisor auto-restart + AGISTATUS dialplan fallback | Prevents silent call failure if AGI daemon crashes |
| Module loading | autoload=no, explicit module loading | Reduces attack surface; disables dangerous dialplan apps |
| Rate effective dates | `effective_date` + `end_date` on rates table | Schedule future rate changes; rating engine picks valid rate at call time |
| Rate CSV import | Background job with validation + preview + error report | Can't add 5000+ rates manually; supports MERGE/REPLACE/ADD_ONLY modes |
| Reseller rate markup | `parent_rate_group_id` links reseller→admin rate group | Enforces minimum margin; reseller can't sell below admin's rate |
| CLI manipulation | Per-trunk `cli_mode` (passthrough/override/strip/translate/hide) | Different providers require different CallerID formats |
| Dial string manipulation | Per-trunk pattern match/replace + prefix + strip pipeline | Different trunks expect different number formats (E.164, national, etc.) |
| SIP account auth modes | password, IP, or both (per-account `auth_type`) | PBX trunking needs IP auth; high-security accounts need both |
| Trunk health monitoring | SIP OPTIONS ping + ASR threshold alerts | Auto-disable dead trunks; flag degraded trunks for admin review |
| Minimum balance threshold | `min_balance_for_calls` per user | Prevents micro-balance abuse; ensures meaningful call duration |

---

## 6. Server Requirements (10M calls/day)

**Recommended: Split into 2 servers (Asterisk + Web/DB)**

### Server 1 — Asterisk (Voice)
- **OS**: Ubuntu 22.04/24.04 LTS or AlmaLinux 9
- **CPU**: 8 cores (transcoding is CPU-heavy)
- **RAM**: 16 GB
- **Storage**: 100 GB SSD
- **Network**: Low latency, dedicated IP
- **Ports**: 5060/UDP (SIP), 10000-20000/UDP (RTP)
- **Software**: Asterisk 21, Redis client

### Server 2 — Web + Database
- **OS**: Ubuntu 22.04/24.04 LTS or AlmaLinux 9
- **CPU**: 8+ cores
- **RAM**: 32 GB (InnoDB buffer pool = 24 GB)
- **Storage**: 4 TB NVMe SSD (call_records ~2.5-3 TB/year with indexes)
- **Ports**: 80/443 (Web), 3306 (MySQL), 6379 (Redis), 4573 (FastAGI)
- **Software**: PHP 8.3, Composer, Node.js 20, MySQL 8, Redis, Supervisor, Nginx

### Single Server (MVP/testing only)
- **CPU**: 8 cores, **RAM**: 32 GB, **Storage**: 4 TB NVMe SSD
- All services on one box — works for development and low-volume testing

---

## 7. Security Architecture

Softswitches are **high-value targets** — compromised systems generate fraudulent calls costing thousands per hour. Security is not optional.

### 7.1 SIP Security (Asterisk Layer)

#### NAT Traversal (Critical — most SIP devices are behind NAT)

All SIP endpoints provisioned via Realtime must include NAT settings:

```ini
; Applied to every ps_endpoint created by Laravel EndpointService
; These columns are set in the ps_endpoints realtime table

rtp_symmetric=yes          ; Send RTP back to where it came from (fixes one-way audio)
force_rport=yes            ; Force rport in SIP responses (fixes NAT signaling)
rewrite_contact=yes        ; Rewrite Contact header with actual source IP
direct_media=no            ; Force all RTP through Asterisk (required for NAT + billing accuracy)
ice_support=no             ; Disable ICE (not needed for server-mediated calls)
```

**Why `direct_media=no` is mandatory:**
- Ensures Asterisk stays in the media path → accurate `billsec` for billing
- Prevents RTP going direct between two NATted endpoints (would fail)
- Allows call recording if needed later

#### SIP Registration Security

```ini
; In pjsip.conf — global settings for registration hardening

[global]
type=global
max_initial_qualify_time=4       ; Fast dead-peer detection
keep_alive_interval=30           ; NAT keepalive every 30s
default_outbound_endpoint=       ; No default endpoint (rejects unknown traffic)

; Transport — TLS for secure signaling (production)
[transport-udp]
type=transport
protocol=udp
bind=0.0.0.0:5060

[transport-tls]
type=transport
protocol=tls
bind=0.0.0.0:5061
cert_file=/etc/asterisk/keys/asterisk.pem
priv_key_file=/etc/asterisk/keys/asterisk.key
ca_list_file=/etc/asterisk/keys/ca.crt
method=tlsv1_2                    ; Minimum TLS 1.2
```

**Endpoint auth hardening** — applied to all SIP account ps_endpoints:

```ini
; ps_endpoints columns set by Laravel EndpointService
auth=<endpoint>-auth              ; Require authentication (never anonymous)
allow_transfer=no                 ; Prevent SIP REFER abuse
max_audio_streams=1               ; Prevent media multiplexing attacks
device_state_busy_at=<max_channels>  ; Enforce max concurrent calls at SIP level
```

**SIP password policy** (enforced by Laravel when creating SIP accounts):
- Minimum 16 characters, auto-generated
- Mix of uppercase, lowercase, numbers, special chars
- Never reuse SIP username as part of password
- Stored hashed in `sip_accounts.password`, plain in `ps_auths.password` (Asterisk requires plain)
- `ps_auths.password` column encrypted at rest (MySQL TDE or application-level encryption)

#### Fail2Ban — SIP Brute Force Protection

```ini
; /etc/fail2ban/jail.d/asterisk.conf
[asterisk]
enabled  = true
filter   = asterisk
action   = iptables-allports[name=asterisk, protocol=all]
logpath  = /var/log/asterisk/security
maxretry = 3                      ; 3 failed auth attempts
findtime = 300                    ; within 5 minutes
bantime  = 3600                   ; ban for 1 hour

[asterisk-aggressive]
enabled  = true
filter   = asterisk
action   = iptables-allports[name=asterisk-aggressive, protocol=all]
logpath  = /var/log/asterisk/security
maxretry = 10                     ; 10 failed attempts
findtime = 86400                  ; within 24 hours
bantime  = 604800                 ; ban for 7 days (persistent attackers)
```

```ini
; /etc/asterisk/logger.conf — enable security logging for fail2ban
[logfiles]
security_log => security
```

#### IP ACL & Firewall Rules

```ini
; /etc/asterisk/acl.conf — restrict SIP traffic by source
[trusted-trunks]
type=acl
deny=0.0.0.0/0.0.0.0             ; deny all by default
; Add each trunk provider's IP ranges:
permit=203.0.113.0/24             ; Trunk Provider 1
permit=198.51.100.0/24            ; Trunk Provider 2

; Applied to trunk endpoints:
; acl=trusted-trunks
```

**iptables / nftables firewall rules:**
```bash
# SIP signaling — only from known trunk IPs + SIP registration range
# (SIP accounts register from dynamic IPs, so port 5060 must be open
#  but fail2ban protects against brute force)

# Management ports — restrict to admin IPs only
iptables -A INPUT -p tcp --dport 22 -s <admin_ip>/32 -j ACCEPT    # SSH
iptables -A INPUT -p tcp --dport 22 -j DROP
iptables -A INPUT -p tcp --dport 8088 -j DROP                      # Block AMI/ARI from outside
iptables -A INPUT -p tcp --dport 5038 -j DROP                      # Block AMI from outside

# RTP media — open the RTP port range
iptables -A INPUT -p udp --dport 10000:20000 -j ACCEPT

# Rate limit SIP REGISTER to slow down scanners
iptables -A INPUT -p udp --dport 5060 -m recent --name sip --set
iptables -A INPUT -p udp --dport 5060 -m recent --name sip \
         --update --seconds 1 --hitcount 30 -j DROP                 # Max 30 SIP packets/sec per IP
```

#### Asterisk Security Hardening (`pjsip.conf` + `asterisk.conf`)

```ini
; /etc/asterisk/asterisk.conf
[options]
runuser = asterisk                ; Never run as root
rungroup = asterisk
live_dangerously = no             ; Block deprecated/unsafe modules

; /etc/asterisk/modules.conf — disable unused modules to reduce attack surface
[modules]
autoload = no                     ; Don't auto-load — explicitly load what we need
load = res_pjsip.so
load = res_pjsip_authenticator_digest.so
load = res_pjsip_endpoint_identifier_ip.so
load = res_pjsip_outbound_authenticator_digest.so
load = res_pjsip_registrar.so
load = res_pjsip_session.so
load = res_pjsip_sdp_rtp.so
load = res_pjsip_nat.so
load = res_odbc.so
load = res_config_odbc.so
load = app_dial.so
load = app_playback.so
load = res_agi.so
load = res_rtp_asterisk.so
load = func_channel.so
load = func_callerid.so
load = func_timeout.so
load = func_cdr.so
load = codec_ulaw.so
load = codec_alaw.so
load = codec_g729.so              ; if licensed
; DO NOT load:
noload = chan_sip.so              ; deprecated, use PJSIP only
noload = cdr_adaptive_odbc.so    ; we handle CDR in AGI
noload = app_system.so           ; prevents System() dialplan abuse
noload = app_exec.so             ; prevents Exec() dialplan abuse
noload = res_http_websocket.so   ; not needed, reduces attack surface
noload = res_ari.so              ; not needed unless using ARI
noload = res_pjsip_phoneprov.so  ; phone provisioning not needed
```

#### Toll Fraud Prevention

```
┌─────────────────────────────────────────────────────────────────┐
│                   TOLL FRAUD PREVENTION LAYERS                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  LAYER 1: Dialplan — restrict what endpoints can dial             │
│  ├── SIP accounts can ONLY reach [from-internal] context          │
│  ├── No access to [from-trunk], no context hopping                │
│  ├── AGI validates every call before routing                      │
│  └── No blind transfers to external numbers (allow_transfer=no)   │
│                                                                   │
│  LAYER 2: AGI Billing Gate — blocks unauthorized calls            │
│  ├── Checks account status (active, not suspended)                │
│  ├── Checks KYC status (must be approved)                         │
│  ├── Checks balance (prepaid) or credit limit (postpaid)          │
│  ├── Checks rate exists for destination (no rate = no call)       │
│  ├── Enforces max concurrent calls per SIP account                │
│  ├── Sets MAX_DURATION to prevent runaway calls                   │
│  └── Logs denied calls for fraud analysis                         │
│                                                                   │
│  LAYER 3: Destination blacklist — block high-cost fraud targets   │
│  ├── International premium rate numbers (IPRN)                    │
│  ├── Satellite destinations (Iridium 8816/8817, Thuraya 88216)    │
│  ├── Cuba (+53), Somalia (+252), other high-fraud destinations    │
│  ├── Admin-managed blacklist in DB (checked by AGI)               │
│  └── Per-user destination whitelist option (restrict to specific  │
│      prefixes only — e.g. client can only dial +880)              │
│                                                                   │
│  LAYER 4: Real-time fraud detection                               │
│  ├── Alert on: >N concurrent calls from single SIP account        │
│  ├── Alert on: calls to new/unusual destination prefix             │
│  ├── Alert on: spike in call volume (>200% of 7-day avg)          │
│  ├── Alert on: short-duration calls to premium numbers (IRSF)     │
│  ├── Auto-suspend: if spend exceeds daily limit                   │
│  └── All alerts via email + SMS to admin                          │
│                                                                   │
│  LAYER 5: Concurrent call limits (enforced at multiple levels)    │
│  ├── Per SIP account: sip_accounts.max_channels (in AGI + PJSIP) │
│  ├── Per client: users.max_channels (sum of all SIP accounts)     │
│  ├── Per trunk: trunks.max_channels (checked via Redis counter)   │
│  └── Global: system-wide max concurrent (Asterisk maxcalls)       │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

**Destination blacklist schema:**

```sql
destination_blacklist
├── id (PK)
├── prefix VARCHAR(20) INDEX       -- e.g. '8816' (Iridium), '53' (Cuba)
├── description VARCHAR(200)       -- e.g. 'Iridium satellite — toll fraud target'
├── applies_to ENUM('all','specific_users')  -- global or per-user
├── user_id INT UNSIGNED NULL      -- if applies_to = 'specific_users'
├── created_by INT UNSIGNED        -- admin who added
├── created_at TIMESTAMP
├── INDEX idx_prefix (prefix)

-- Per-user destination whitelist (optional — restricts user to specific prefixes only)
destination_whitelist
├── id (PK)
├── user_id INT UNSIGNED INDEX     -- client or reseller
├── prefix VARCHAR(20)             -- e.g. '880' (Bangladesh only)
├── description VARCHAR(200)
├── created_by INT UNSIGNED
├── created_at TIMESTAMP
├── INDEX idx_user_prefix (user_id, prefix)
```

**Fraud detection fields — add to `users` table:**

```sql
-- Additional columns on users table for fraud prevention
├── max_channels INT UNSIGNED DEFAULT 10     -- max concurrent calls for this user (all SIP accounts combined)
├── daily_spend_limit DECIMAL(10,4) NULL     -- auto-suspend if exceeded (NULL = no limit)
├── daily_call_limit INT UNSIGNED NULL       -- max calls per day (NULL = no limit)
├── destination_whitelist_enabled BOOLEAN DEFAULT FALSE  -- if true, only whitelisted prefixes allowed
```

### 7.2 Web Application Security (Laravel Layer)

#### Authentication & Session Security

```php
// config/session.php
'lifetime' => 120,              // 2-hour session timeout
'expire_on_close' => false,
'encrypt' => true,              // Encrypt session data
'secure' => true,               // HTTPS-only cookies (production)
'http_only' => true,            // No JavaScript access to session cookie
'same_site' => 'lax',          // CSRF protection

// config/auth.php — password rules
'passwords' => [
    'min' => 12,                // Minimum 12 characters for web login
    'mixed_case' => true,
    'numbers' => true,
    'symbols' => true,
],
```

**Two-Factor Authentication (2FA) — mandatory for Admin, optional for others:**

```
┌──────────────────────────────────────────────────┐
│                2FA ENFORCEMENT                    │
├──────────────────────────────────────────────────┤
│ Admin:    MANDATORY (TOTP — Google Authenticator) │
│ Reseller: Optional (strongly recommended)         │
│ Client:   Optional                                │
│                                                   │
│ Recovery: 10 one-time backup codes on 2FA setup   │
│ Storage:  Encrypted secret in users.two_fa_secret │
│ Library:  pragmarx/google2fa-laravel              │
└──────────────────────────────────────────────────┘
```

**Additional users table columns for 2FA:**
```sql
├── two_fa_enabled BOOLEAN DEFAULT FALSE
├── two_fa_secret VARCHAR(255) NULL        -- encrypted TOTP secret
├── two_fa_recovery_codes JSON NULL        -- encrypted backup codes
├── two_fa_confirmed_at TIMESTAMP NULL
```

#### Rate Limiting & Brute Force Protection

```php
// routes/web.php — Laravel rate limiting
Route::middleware(['throttle:login'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['throttle:api'])->group(function () {
    Route::post('/api/webhooks/*');  // Payment gateway callbacks
});

// app/Providers/RouteServiceProvider.php
RateLimiter::for('login', function (Request $request) {
    return [
        Limit::perMinute(5)->by($request->ip()),           // 5 attempts/min per IP
        Limit::perMinute(10)->by($request->input('email')), // 10 attempts/min per email
    ];
});

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->ip());
});
```

**Account lockout policy:**
- 5 failed logins → 15-minute lockout
- 15 failed logins → 1-hour lockout + email notification to account owner
- 50 failed logins in 24h → account locked until admin intervenes
- All failed login attempts logged with IP, user agent, timestamp

#### CSRF, XSS, SQL Injection

```php
// Laravel provides these by default, but verify:
// 1. CSRF: @csrf token in all forms (Blade does this automatically)
// 2. XSS: {{ $var }} auto-escapes output (never use {!! $var !!} with user input)
// 3. SQL Injection: Always use Eloquent/Query Builder parameterized queries
//    NEVER use DB::raw() with user input
// 4. Mass assignment: All models must define $fillable (not $guarded = [])

// Content Security Policy header (in Nginx or middleware)
// Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'

// Additional security headers (Nginx)
// X-Frame-Options: DENY
// X-Content-Type-Options: nosniff
// X-XSS-Protection: 1; mode=block
// Strict-Transport-Security: max-age=31536000; includeSubDomains
// Referrer-Policy: strict-origin-when-cross-origin
```

#### API & Webhook Security

```php
// Payment gateway webhooks — verify signatures
// Stripe:  Stripe-Signature header → webhook secret verification
// PayPal:  IPN verification POST back to PayPal
// SSLCommerz: verify_sign + store_id + store_passwd validation

// All webhook endpoints:
// 1. Verify gateway signature BEFORE processing
// 2. Check for replay attacks (store processed transaction IDs, reject duplicates)
// 3. Respond with 200 OK quickly (process async via queue if heavy)
// 4. Log full request for audit
```

### 7.3 Infrastructure Security

#### Network Segmentation (2-server setup)

```
┌──────────────────────────────────────────────────────────────┐
│                    NETWORK ARCHITECTURE                        │
├──────────────────────────────────────────────────────────────┤
│                                                                │
│  PUBLIC INTERNET                                               │
│  │                                                             │
│  ├──→ Server 1 (Asterisk) — public IP                         │
│  │    ├── Port 5060/UDP: SIP signaling (open, fail2ban-protected)│
│  │    ├── Port 5061/TCP: SIP TLS (open, for secure clients)    │
│  │    ├── Port 10000-20000/UDP: RTP media                      │
│  │    ├── Port 22/TCP: SSH (admin IPs only)                    │
│  │    └── ALL other ports: BLOCKED                             │
│  │                                                             │
│  ├──→ Server 2 (Web+DB) — public IP for HTTPS only            │
│  │    ├── Port 443/TCP: HTTPS (Nginx → Laravel)                │
│  │    ├── Port 80/TCP: HTTP → redirect to HTTPS                │
│  │    ├── Port 22/TCP: SSH (admin IPs only)                    │
│  │    └── ALL other ports: BLOCKED from public                 │
│  │                                                             │
│  PRIVATE NETWORK (between Server 1 ↔ Server 2)                │
│  │    ├── Port 3306: MySQL (Asterisk realtime + Laravel)       │
│  │    ├── Port 6379: Redis (call state, counters)              │
│  │    ├── Port 4573: FastAGI (Asterisk → Laravel AGI)          │
│  │    ├── Port 5038: AMI (Laravel → Asterisk management)       │
│  │    └── Bound to private/internal IPs only                   │
│  │                                                             │
│  Key rules:                                                    │
│  ├── MySQL, Redis, FastAGI, AMI — NEVER exposed to public     │
│  ├── Redis requires password (requirepass in redis.conf)       │
│  ├── MySQL user for Asterisk has SELECT-only on realtime tables│
│  └── MySQL user for Laravel has full access to app database    │
│                                                                │
└──────────────────────────────────────────────────────────────┘
```

#### Database Security

```sql
-- Asterisk MySQL user — minimal privileges (read-only on realtime tables)
CREATE USER 'asterisk_rt'@'<asterisk_server_ip>' IDENTIFIED BY '<strong_password>';
GRANT SELECT ON rswitch.ps_endpoints TO 'asterisk_rt'@'<asterisk_server_ip>';
GRANT SELECT ON rswitch.ps_auths TO 'asterisk_rt'@'<asterisk_server_ip>';
GRANT SELECT ON rswitch.ps_aors TO 'asterisk_rt'@'<asterisk_server_ip>';
GRANT SELECT, INSERT, UPDATE, DELETE ON rswitch.ps_contacts TO 'asterisk_rt'@'<asterisk_server_ip>';

-- Laravel MySQL user — full application access
CREATE USER 'rswitch_app'@'<web_server_ip>' IDENTIFIED BY '<strong_password>';
GRANT ALL PRIVILEGES ON rswitch.* TO 'rswitch_app'@'<web_server_ip>';

-- No root remote access
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
FLUSH PRIVILEGES;
```

**Redis security:**
```ini
# /etc/redis/redis.conf
bind 127.0.0.1 <private_ip>      # Only local + private network
requirepass <strong_redis_password>
rename-command FLUSHALL ""         # Disable dangerous commands
rename-command FLUSHDB ""
rename-command CONFIG ""           # Disable runtime config changes
rename-command DEBUG ""
```

#### SSL/TLS Certificates

```
Web (Nginx):     Let's Encrypt auto-renewal (certbot)
SIP TLS:         Self-signed CA or Let's Encrypt (for SIP endpoints supporting TLS)
MySQL:           TLS between servers (require_secure_transport=ON)
Redis:           TLS if crossing network boundaries (stunnel or Redis 6+ native TLS)
```

#### Audit Logging

```sql
-- All admin actions logged (separate from business transaction logs)
audit_logs
├── id (PK, BIGINT)
├── user_id INT UNSIGNED            -- who performed the action
├── action VARCHAR(100)             -- e.g. 'user.create', 'sip_account.delete', 'trunk.update'
├── auditable_type VARCHAR(100)     -- model class name
├── auditable_id BIGINT UNSIGNED    -- model ID
├── old_values JSON NULL            -- before change
├── new_values JSON NULL            -- after change
├── ip_address VARCHAR(45)
├── user_agent VARCHAR(500)
├── created_at TIMESTAMP
├── INDEX idx_user_date (user_id, created_at)
├── INDEX idx_auditable (auditable_type, auditable_id)
├── INDEX idx_action (action, created_at)
```

**What gets audited:**
- All user CRUD (create, update, delete, suspend, unsuspend)
- All SIP account changes
- All trunk and route changes
- All balance operations (recharge, debit, adjustment)
- All transfer operations
- KYC approval/rejection
- Login/logout events (success and failure)
- Rate changes
- System setting changes

**Implementation**: Use `owen-it/laravel-auditing` package or custom Eloquent observer.

### 7.4 AGI Daemon Resilience

```
┌──────────────────────────────────────────────────────────────┐
│                AGI FAILURE HANDLING                            │
├──────────────────────────────────────────────────────────────┤
│                                                                │
│  Problem: If FastAGI daemon crashes, ALL calls fail silently   │
│                                                                │
│  Solution: Multi-layer protection                              │
│                                                                │
│  1. Supervisor — auto-restart AGI daemon                       │
│     [program:agi-daemon]                                       │
│     command=php /var/www/rswitch/agi/server.php                │
│     autostart=true                                             │
│     autorestart=true                                           │
│     startretries=10                                            │
│     startsecs=5                                                │
│     stderr_logfile=/var/log/rswitch/agi-error.log              │
│     stdout_logfile=/var/log/rswitch/agi-output.log             │
│                                                                │
│  2. Asterisk dialplan fallback — if AGI fails, don't silently  │
│     drop the call. Play an error message:                      │
│                                                                │
│     exten => _X.,n,AGI(agi://127.0.0.1:4573,...)              │
│     same  => n,GotoIf($["${AGISTATUS}" = "FAILURE"]?agi_fail) │
│     ...                                                        │
│     same  => n(agi_fail),Playback(tt-allbusy)                  │
│     same  => n,Hangup()                                        │
│                                                                │
│  3. Health check — AsteriskHealthCheckCommand pings AGI port   │
│     every minute. If unreachable:                              │
│     → Attempt restart via Supervisor                           │
│     → Alert admin via email/SMS                                │
│     → Log incident                                             │
│                                                                │
│  4. Multiple AGI worker processes (pool of 4-8 workers)        │
│     → One crash doesn't kill all call routing                  │
│     → Supervisor manages the pool                              │
│                                                                │
└──────────────────────────────────────────────────────────────┘
```

### 7.5 Security Checklist Summary

| Area | Measure | Priority |
|---|---|---|
| **SIP** | Fail2ban on Asterisk security log | P0 — Day 1 |
| **SIP** | NAT: rtp_symmetric, force_rport, rewrite_contact, direct_media=no | P0 — Day 1 |
| **SIP** | Strong auto-generated SIP passwords (16+ chars) | P0 — Day 1 |
| **SIP** | Disable unused Asterisk modules (app_system, chan_sip, etc.) | P0 — Day 1 |
| **SIP** | Run Asterisk as non-root user | P0 — Day 1 |
| **SIP** | Trunk IP ACLs (acl.conf + iptables) | P0 — Day 1 |
| **SIP** | Toll fraud: destination blacklist + daily spend limits | P0 — Day 1 |
| **SIP** | Concurrent call limits per user/SIP account/trunk | P0 — Day 1 |
| **SIP** | SIP TLS transport (port 5061) | P1 — Before production |
| **SIP** | SRTP for media encryption | P2 — Post-MVP |
| **Web** | HTTPS everywhere (Let's Encrypt) | P0 — Day 1 |
| **Web** | CSRF protection (Laravel default) | P0 — Day 1 |
| **Web** | Rate limiting on login (5/min per IP) | P0 — Day 1 |
| **Web** | Security headers (CSP, HSTS, X-Frame-Options) | P0 — Day 1 |
| **Web** | 2FA for admin accounts (TOTP) | P1 — Before production |
| **Web** | Account lockout after failed logins | P1 — Before production |
| **Web** | Audit logging on all admin actions | P1 — Before production |
| **Infra** | MySQL/Redis/AMI/AGI on private network only | P0 — Day 1 |
| **Infra** | Minimal MySQL privileges for Asterisk user (SELECT-only) | P0 — Day 1 |
| **Infra** | Redis password + disabled dangerous commands | P0 — Day 1 |
| **Infra** | SSH key-only auth, no root login | P0 — Day 1 |
| **Infra** | AGI daemon auto-restart via Supervisor | P0 — Day 1 |
| **Infra** | AGI failure fallback in dialplan (AGISTATUS check) | P0 — Day 1 |
| **Infra** | Automated backups (MySQL dump + Redis RDB) | P1 — Before production |
| **Infra** | Log rotation (Asterisk, Laravel, Nginx) | P1 — Before production |

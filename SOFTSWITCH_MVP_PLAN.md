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
Summary tables are updated every 5 minutes by a scheduled job.

```sql
-- Hourly summary per user (for dashboards, reports)
cdr_summary_hourly
├── id (PK, BIGINT)
├── user_id INT UNSIGNED
├── reseller_id INT UNSIGNED NULL
├── hour_start DATETIME              -- e.g. '2026-02-04 14:00:00'
├── call_flow ENUM('sip_to_trunk','sip_to_sip','trunk_to_sip','trunk_to_trunk')
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
├── UNIQUE idx_user_hour_flow (user_id, hour_start, call_flow)
├── INDEX idx_reseller_hour (reseller_id, hour_start)
├── PARTITION BY RANGE (TO_DAYS(hour_start)) -- same monthly partitioning

-- Daily summary per user (for invoicing, monthly reports)
cdr_summary_daily
├── id (PK, BIGINT)
├── user_id INT UNSIGNED
├── reseller_id INT UNSIGNED NULL
├── date DATE
├── call_flow ENUM('sip_to_trunk','sip_to_sip','trunk_to_sip','trunk_to_trunk')
├── total_calls INT UNSIGNED DEFAULT 0
├── answered_calls INT UNSIGNED DEFAULT 0
├── total_duration INT UNSIGNED DEFAULT 0
├── total_billable INT UNSIGNED DEFAULT 0
├── total_cost DECIMAL(12,4) DEFAULT 0
├── total_reseller_cost DECIMAL(12,4) DEFAULT 0
├── asr DECIMAL(5,2) NULL
├── acd DECIMAL(8,2) NULL
├── updated_at TIMESTAMP
├── UNIQUE idx_user_date_flow (user_id, date, call_flow)
├── INDEX idx_reseller_date (reseller_id, date)

-- Daily summary per destination prefix (for rate analysis)
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

Step 3 — External call: rate lookup
  → Longest prefix match on rates table using caller's rate_group_id
  → If NO rate found → Set ROUTE_TYPE = "denied", RETURN

Step 4 — External call: balance check
  → Prepaid:  max_duration = (balance / rate_per_minute) * 60
              If balance <= 0 → DENIED
  → Postpaid: remaining = credit_limit - outstanding_charges
              If remaining <= 0 → DENIED
  → Set MAX_DURATION (seconds)

Step 5 — External call: select outgoing trunk (prefix + time-based)
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
  → Pick TRUNK_1 (primary, lowest priority number in time window)
  → Pick TRUNK_2 (failover, next priority or NULL-time fallback route)
  → If NO trunk found → DENIED

Step 6 — Create call_record + cache in Redis
  → INSERT call_records (status='in_progress', call_start=NOW(),
      user_id, sip_account_id, caller, callee, rate_per_minute,
      matched_prefix, destination, outgoing_trunk_id, call_flow='sip_to_trunk')
  → Cache in Redis: call:{channel_id} = {call_record_id, rate_per_minute,
      billing_increment, min_duration, connection_fee, user_id}
  → TTL = 7200 seconds (2hr safety net)

Step 7 — Return variables
  → ROUTE_TYPE  = "trunk"
  → TRUNK_1     = trunk endpoint name (e.g. "trunk-outgoing-1")
  → TRUNK_2     = failover trunk endpoint name (or empty)
  → TRUNK_PREFIX= trunk's tech prefix
  → TRUNK_STRIP = trunk's strip_digits
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
│   │   │   │   ├── ManualRechargeController.php  -- admin recharges any user
│   │   │   │   ├── PaymentHistoryController.php  -- view all payments system-wide
│   │   │   │   └── SystemSettingController.php
│   │   │   ├── Reseller/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── ClientController.php
│   │   │   │   ├── SipAccountController.php
│   │   │   │   ├── DidController.php
│   │   │   │   ├── RateGroupController.php
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
│   │   │   ├── CallRecordService.php   -- Insert at call start, update at call end
│   │   │   ├── InvoiceGenerator.php   -- Monthly invoice creation
│   │   │   └── PaymentService.php     -- Online payment + manual recharge logic
│   │   ├── PaymentGateway/
│   │   │   ├── PaymentGatewayInterface.php  -- common interface
│   │   │   ├── StripeGateway.php      -- Stripe Checkout / Payment Intent
│   │   │   ├── PaypalGateway.php      -- PayPal integration
│   │   │   └── SslcommerzGateway.php  -- SSLCommerz (BD local gateway)
│   │   ├── Kyc/
│   │   │   └── KycService.php         -- submit, validate docs, approve/reject
│   │   └── Did/
│   │       └── DidService.php
│   ├── Notifications/
│   │   ├── KycSubmittedNotification.php   -- notify admin when KYC submitted
│   │   ├── KycApprovedNotification.php    -- notify user when KYC approved
│   │   ├── KycRejectedNotification.php    -- notify user when KYC rejected (with reason)
│   │   ├── PaymentReceivedNotification.php   -- notify user on successful recharge
│   │   ├── PaymentFailedNotification.php     -- notify user on failed payment
│   │   └── LowBalanceNotification.php        -- notify when balance below threshold
│   ├── Jobs/
│   │   ├── UpdateCdrSummaryJob.php    -- Runs every 5 min, updates hourly/daily summaries
│   │   ├── RateHangupJob.php          -- Queued: rate + debit on call end (async fallback)
│   │   ├── GenerateInvoicesJob.php    -- Monthly
│   │   ├── SuspendOverdueJob.php      -- Check postpaid limits
│   │   └── ProvisionEndpointJob.php
│   ├── Scopes/
│   │   └── TenantScope.php           -- Global scope for hierarchy filtering
│   ├── Observers/
│   │   └── SipAccountObserver.php    -- Sync to Asterisk realtime on create/update/delete
│   └── Console/
│       └── Commands/
│           ├── UpdateCdrSummaryCommand.php     -- every 5 min
│           ├── CreateCdrPartitionCommand.php   -- monthly: create next 2 partitions
│           ├── ArchiveCdrPartitionCommand.php  -- monthly: export + drop > 12 months
│           ├── CompressCdrPartitionCommand.php -- monthly: compress 6-12 month partitions
│           ├── RecoverStalledCallsCommand.php  -- hourly: find in_progress > 2hrs, mark failed
│           ├── GenerateInvoicesCommand.php
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
| View invoices | All | Own tree | Own |
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
- **Deliverable**: Login, user management with hierarchy, KYC workflow, Asterisk running

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
- `call_records` table with monthly partitioning + partition auto-creation command
- Redis caching for active call state
- Basic CDR viewer (call_records, paginated with partition pruning)
- **Deliverable**: Working calls — SIP accounts register, outbound via trunks, inbound via DIDs

### Phase 3: Billing Engine
- Rate group and rate management (prefix-based)
- Rating engine (longest prefix match)
- **Real-time billing via hangup handler AGI** (no batch CDR processor):
  - AGI inserts `call_records` at call start, caches rate in Redis
  - Hangup handler AGI calculates cost, updates record, debits balance
  - `RecoverStalledCallsCommand` — hourly cleanup for orphaned in_progress records
- Balance service (atomic credit/debit with row locking)
- Prepaid: AGI balance check before call, max duration enforcement
- Postpaid: credit limit enforcement
- Transaction log
- Top-up functionality (admin/reseller adds credit to accounts)
- **CDR summary tables** (hourly + daily) with 5-minute update job
- **Deliverable**: Calls are rated in real-time, balances deducted instantly, prepaid cutoff works

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
  - Stats from `cdr_summary_hourly`/`cdr_summary_daily` (millisecond queries)
- Reseller dashboard (own clients stats, revenue, balance)
- Client dashboard (call stats, balance, recent calls)
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
| CDR reporting | Pre-aggregated summary tables (hourly/daily) | Dashboards never query 3.6B row table; millisecond stats |
| Active call state | Redis (TTL 2hr) | Sub-millisecond reads for hangup handler; no DB query during billing |
| Balance tracking | MySQL row-level locking + Redis cache | Atomic operations; Redis for fast AGI reads |
| Auth/permissions | Spatie Laravel Permission | Battle-tested; supports role hierarchy |
| Frontend | Blade + Livewire (or Inertia + Vue) | Rapid development for MVP |
| Trunk direction | Single table with direction ENUM | Avoids duplicate schemas; one trunk can handle both directions |
| Outgoing trunk selection | Prefix + time-based routing with priority + failover | Longest prefix match + time window filter; AGI returns primary + backup trunk |
| Incoming trunk auth | IP-based (identify) or registration | Flexible per provider; most ITSP use IP-based |
| Trunk provisioning | Laravel generates pjsip_trunks.conf + AMI reload | Keeps trunk config in sync; no manual Asterisk editing |

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

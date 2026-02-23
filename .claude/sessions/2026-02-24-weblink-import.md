# Session Log: Weblink Data Migration - Feb 24, 2026

## Task
Migrate production data from old `weblink.sql` (rbilling_iptsp MariaDB billing system) into rSwitch Laravel application using `php artisan import:weblink` command.

## File Created
- `app/Console/Commands/ImportWeblink.php` - Complete import command

## Final Import Results (Successful)

| Table | Imported |
|---|---|
| users | 10,064 |
| rate_groups | 12 |
| rates | 111 |
| trunks | 13 |
| trunk_routes | 47 |
| sip_accounts | 9,894 |
| sip_accounts (provisioned) | 9,894 |

### User Role Breakdown
| Role | Count |
|---|---|
| super_admin | 12 |
| client | 10,021 |
| reseller | 30 |
| recharge_admin | 1 |

## Issues Fixed During Development

1. **Shell injection in mysql command** - Used `MYSQL_PWD` env var instead of raw password in command string
2. **FK violation on rate_groups.created_by** - Reordered: users first, then rate groups
3. **proc_open env array non-strings** - Simplified to `['MYSQL_PWD' => ..., 'PATH' => ...]`
4. **Bcrypt too slow for 10K users** - Single pre-computed default password (`ChangeMe123!`)
5. **Duplicate source account IDs (MyISAM no PK)** - Added `GROUP BY id`
6. **Duplicate emails across accounts** - Hash-based dedup + DB-level check
7. **DROP DATABASE before CREATE** - Handle stale temp DB on re-runs
8. **Trunk routes 0 imported** - `dialplan.gateway_id` references `accounts.id` (gateway accounts), NOT `sipusers.id`. Added `$gatewayMap` mapping `sipusers.id_client` → new trunk ID
9. **SIP provisioning "Column 'allow' cannot be null"** - Added `'codec_allow' => 'ulaw,alaw,g729'` to `SipAccount::create()` call

## Key Discovery
The `dialplan.gateway_id` column references `accounts.id` (where clienttype=3, gateway accounts), not `sipusers.id` directly. The relationship chain is:
```
dialplan.gateway_id → accounts.id (clienttype=3) → sipusers.id_client = accounts.id (type='peer') → trunk
```

## Pending / Next Steps
- [ ] All users have default password `ChangeMe123!` - password resets required post-migration
- [ ] Deploy to production server (103.170.231.19) after local testing
- [ ] Verify login as imported super_admin user
- [ ] Verify SIP accounts, rate groups, and routes are browsable in UI
- [ ] Test actual SIP registration and call routing with imported data

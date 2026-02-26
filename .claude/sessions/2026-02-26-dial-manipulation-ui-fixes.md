# Session Log: Dial Manipulation + UI Fixes - Feb 26, 2026

## Tasks Completed

### 1. Weblink Import - Final Fixes (continued from Feb 24)
- Fixed **trunk routes** (0 → 47): `dialplan.gateway_id` references `accounts.id`, not `sipusers.id`. Added `$gatewayMap` mapping via `sipusers.id_client`
- Fixed **SIP provisioning** failures: Added `'codec_allow' => 'ulaw,alaw,g729'` to `SipAccount::create()`
- Full import now succeeds: 10,064 users, 12 rate groups, 111 rates, 13 trunks, 47 trunk routes, 9,894 SIP accounts (all provisioned)

### 2. Dial Manipulation on Routing Rules
Added `remove_prefix` and `add_prefix` fields to trunk routes (routing rules).

**Files changed:**
- `database/migrations/2026_02_26_114317_add_dial_manipulation_to_trunk_routes_table.php` (new)
- `app/Models/TrunkRoute.php` - Added to fillable, new `applyDialPrefixManipulation()` method
- `app/Http/Controllers/Admin/TrunkRouteController.php` - Validation rules
- `resources/views/admin/trunk-routes/create.blade.php` - Dial Manipulation form card
- `resources/views/admin/trunk-routes/edit.blade.php` - Same form card with existing values
- `app/Services/Agi/OutboundCallHandler.php` - Applied before MNP in call chain
- `app/Console/Commands/ImportWeblink.php` - Maps source dialplan fields

**Call processing order:**
```
Remove Prefix → Add Prefix → MNP Dipping → Trunk Manipulation
```

### 3. Rate Group Details Table Fix
- Added `data-table-compact` CSS class (py-2.5 px-4 vs py-4 px-6)
- Fixed rate from `$0.450000` (6 decimals) to `format_currency($rate, 4)`
- Added `whitespace-nowrap` on date column
- Action buttons aligned inline with `flex gap-1`, icons reduced to w-4 h-4

**Files changed:**
- `resources/views/admin/rate-groups/show.blade.php`
- `resources/css/app.css` - Added `.data-table-compact` class

### 4. Dashboard Platform Overview Fix
- Changed from 6 cramped vertical cards in `grid-cols-6` to horizontal layout
- Now uses icon-left text-right layout in a `grid-cols-3` (2 rows x 3 cols)
- Consistent `flex items-center gap-3 p-3` on each card

**File changed:**
- `resources/views/admin/dashboard.blade.php`

## Commits
- `e15c907` - Add dial manipulation (remove/add prefix) to routing rules
- `d6c3c33` - Fix rate group details table: compact rows, formatting, aligned actions
- `aa131a5` - Fix dashboard Platform Overview layout with horizontal stat cards

## Deployment
All changes deployed to production (103.170.231.19) via:
1. `tar` + `scp` files to `/tmp/`
2. `sudo cp` into `/var/www/rswitch/`
3. `npm run build`, `php artisan migrate --force`, `view:cache`, `config:cache`, `route:cache`

Note: The `installer/update.sh` backup step fails due to missing `/root/rswitch-credentials.txt`. Deployed manually instead.

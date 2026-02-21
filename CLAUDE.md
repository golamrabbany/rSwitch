# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

rSwitch is a multi-tenant VoIP billing and switching platform built with Laravel 12, designed to manage SIP accounts, DIDs, trunks, call routing, and billing for resellers and clients.

## Common Commands

```bash
# Start development environment
./vendor/bin/sail up -d

# Run tests
./vendor/bin/sail test

# Run specific test file
./vendor/bin/sail test tests/Feature/Admin/UserManagementTest.php

# Run specific test method
./vendor/bin/sail test --filter=test_admin_can_authenticate_with_otp

# Run migrations
./vendor/bin/sail artisan migrate

# Fresh migration with seeding
./vendor/bin/sail artisan migrate:fresh --seed

# Build frontend assets
./vendor/bin/sail npm run build

# Check Asterisk status
docker exec rswitch-asterisk-1 asterisk -rx "pjsip show endpoints"

# Run billing commands
./vendor/bin/sail artisan billing:rate-calls
./vendor/bin/sail artisan billing:generate-invoices
./vendor/bin/sail artisan cdr:aggregate
```

## Architecture

### User Hierarchy & Roles

The system uses a hierarchical multi-tenant model with 5 roles:

| Role | Parent | Can Manage |
|------|--------|------------|
| `super_admin` | None | Everything |
| `admin` | None | Assigned resellers + their clients |
| `recharge_admin` | None | View-only + balance operations for assigned resellers |
| `reseller` | None | Own clients only |
| `client` | Reseller | Own resources only |

**Key Model Methods** (in `app/Models/User.php`):
- `descendantIds()` - Get all user IDs in hierarchy (uses materialized path)
- `canManage(User $target)` - Authorization check
- `managedResellerIds()` - Get reseller IDs user can access
- `isSuperAdmin()`, `isRegularAdmin()`, `isRechargeAdmin()`, `isReseller()`, `isClient()` - Role checks

### User Model Traits

The User model is organized into traits in `app/Models/Traits/`:

- **HasRoleHelpers** - Role check methods and billing type helpers
- **HasHierarchy** - Materialized path pattern for efficient descendant queries with caching
- **HasAuthorization** - `canManage()`, `scopeVisibleTo()`, scoping logic

### Authentication

Two separate login flows:

- **Admins** (`super_admin`, `admin`, `recharge_admin`): OTP-based login at `/admin/login` via `AdminOtpLoginController`
- **Clients & Resellers**: Standard Livewire login at `/login` via `LoginForm`

After login, `/dashboard` redirects to role-based dashboard via a match statement in `routes/web.php`.

**Impersonation**: Super Admin can "Login As" any non-super-admin user via `ImpersonationController`. Session stores `impersonator_id`.

### Key Services

| Service | Location | Purpose |
|---------|----------|---------|
| `BalanceService` | `app/Services/BalanceService.php` | Atomic credit/debit with row locking |
| `RatingService` | `app/Services/RatingService.php` | CDR rating with longest-prefix match |
| `SipProvisioningService` | `app/Services/SipProvisioningService.php` | Asterisk PJSIP realtime provisioning |
| `TrunkProvisioningService` | `app/Services/TrunkProvisioningService.php` | Trunk config generation + AMI reload |
| `RouteSelectionService` | `app/Services/RouteSelectionService.php` | Outbound trunk selection algorithm |
| `AuditService` | `app/Services/AuditService.php` | Action logging to audit_logs table |
| `DashboardStatsService` | `app/Services/DashboardStatsService.php` | Dashboard statistics queries |

### Database Structure

**Core Tables:**
- `users` - Multi-tenant with `parent_id`, `hierarchy_path`, `role` enum
- `sip_accounts` - PJSIP endpoints with auth modes (password/IP/both)
- `dids` - Inbound numbers with destination routing
- `trunks` - SIP providers for inbound/outbound
- `trunk_routes` - LCR routing rules with time-based filtering
- `call_records` - CDR table (monthly partitioned by `call_start`)
- `rate_groups` / `rates` - Billing rates with longest-prefix matching
- `transactions` / `payments` / `invoices` - Financial records

**Asterisk Realtime Tables:**
- `ps_endpoints`, `ps_auths`, `ps_aors`, `ps_contacts`, `ps_endpoint_id_ips`

### Middleware

- `RoleMiddleware` (`role:admin,reseller`) - Route-level role checks
- `KycApprovedMiddleware` - Blocks features until KYC approved
- `SecurityHeaders` - Adds security headers to responses

### Route Structure

```
/admin/login       - Admin OTP login (super_admin, admin, recharge_admin)
/login             - Client/Reseller Livewire login
/admin/*           - Super Admin & Regular Admin routes
/recharge-admin/*  - Recharge Admin routes (view-only + balance)
/reseller/*        - Reseller portal
/client/*          - Client portal
/api/v1/*          - REST API (Sanctum auth)
```

### View Structure

Layouts in `resources/views/layouts/` — each portal has its own layout file. All use indigo as brand color. Components in `resources/views/components/` wrap the layouts.

CSS theme classes are in `resources/css/app.css`: `.theme-admin`, `.theme-reseller`, `.theme-client` control sidebar active/hover states.

### Global Helpers

`app/Helpers/currency.php` is autoloaded via composer and provides `format_currency()`.

## Coding Patterns

### Scoping Queries

Always scope queries for non-super-admin users:

```php
$authUser = auth()->user();
$query = User::with('parent');

if (!$authUser->isSuperAdmin()) {
    $query->visibleTo($authUser);
}
```

### Balance Operations

Always use BalanceService for atomic operations:

```php
$balanceService->credit(
    user: $user,
    amount: $amount,
    type: 'topup',
    referenceType: 'manual_admin',
    description: "Admin topup",
    createdBy: auth()->id(),
    source: 'bank_transfer',
    remarks: $reason,
);
```

### Audit Logging

Log significant actions:

```php
AuditService::logCreated($model, 'action.name');
AuditService::logUpdated($model, $originalAttributes, 'action.name');
AuditService::logAction('custom.action', $model, ['extra' => 'data']);
```

### Authorization Checks

```php
abort_unless(auth()->user()->canManage($user), 403);
abort_unless(auth()->user()->canRechargeBalance($user), 403);
```

## Docker Services

Defined in `compose.yaml`:
- `laravel.test` - Main Laravel application (Sail)
- `mysql` - MySQL 8.4
- `redis` - Cache and queues
- `asterisk` - Asterisk 21.x with PJSIP/ODBC
- `phpmyadmin` - Database management (port 8080)
- `worker` - Queue worker via Supervisor

Port conflicts: If XAMPP or local MySQL is running, set `FORWARD_DB_PORT=3307` and `APP_PORT=8000` in `.env`.

## Production Server

- **Host**: 103.170.231.19
- **User**: sarker
- **Password**: Sarker@!#$
- **Domain**: rswitch.webvoice.net
- **Install Dir**: /var/www/rswitch
- **SSH**: `sshpass -f <(printf '%s' $'Sarker@!#$') ssh sarker@103.170.231.19`
- **No git repo** on server — deploy by uploading files to `/tmp/` then `sudo cp` into place

## Production Installer

The `installer/` directory contains bash scripts for bare-metal server deployment:
- `install.sh` - Full installation (PHP, MySQL, Redis, Nginx, Asterisk, ODBC)
- `update.sh` - Application update with backup
- `uninstall.sh` - Clean removal
- `troubleshoot.sh` - Diagnostic tool
- `templates/` - Nginx, Supervisor, Asterisk, Fail2Ban config templates

Supports Ubuntu 22.04+, Debian 12+, CentOS 9+, AlmaLinux 9+.

## Important Notes

1. **Hierarchy Path**: Users have a `hierarchy_path` column (e.g., `/1/5/12/`) for efficient descendant queries. Auto-generated via the `HasHierarchy` trait.

2. **CDR Partitioning**: The `call_records` table is partitioned by month on `call_start`. Always include `call_start` in WHERE clauses.

3. **SIP Provisioning**: SIP accounts are provisioned to Asterisk realtime tables automatically via `SipProvisioningService`.

4. **Admin-Reseller Assignment**: Regular Admins and Recharge Admins are assigned to specific resellers via `admin_resellers` pivot table.

5. **Rate Groups**: Users are assigned rate groups. Billing uses longest-prefix-match on `rates.prefix`.

6. **Two Login Systems**: Admins use OTP at `/admin/login`, clients/resellers use standard Livewire at `/login`. Do not mix these.

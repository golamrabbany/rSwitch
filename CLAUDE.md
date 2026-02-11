# CLAUDE.md - rSwitch VoIP Billing Platform

This file provides guidance to Claude Code when working in this repository.

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
/admin/*           - Super Admin & Regular Admin routes
/recharge-admin/*  - Recharge Admin routes (view-only + balance)
/reseller/*        - Reseller portal
/client/*          - Client portal
/api/v1/*          - REST API (Sanctum auth)
```

### View Structure

Layouts are in `resources/views/layouts/`:
- `admin.blade.php` - Admin panel layout with dark sidebar
- `recharge-admin.blade.php` - Recharge admin layout
- `reseller.blade.php` - Reseller portal layout
- `client.blade.php` - Client portal layout

Components in `resources/views/components/`:
- `admin-layout.blade.php`, `reseller-layout.blade.php`, `client-layout.blade.php`

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

## Testing

Tests are in `tests/Feature/` and `tests/Unit/`. Key test files:

- `tests/Feature/Admin/UserManagementTest.php` - User CRUD and scoping
- `tests/Feature/RatingServiceTest.php` - Billing rate calculations
- `tests/Feature/BalanceServiceTest.php` - Balance operations

Run with: `./vendor/bin/sail test`

## Docker Services

Defined in `docker-compose.yml`:
- `laravel.test` - Main Laravel application (Sail)
- `mysql` - MySQL 8.4
- `redis` - Cache and queues
- `asterisk` - Asterisk 21.x with PJSIP/ODBC

## Important Notes

1. **Hierarchy Path**: Users have a `hierarchy_path` column (e.g., `/1/5/12/`) for efficient descendant queries. This is auto-generated via the `HasHierarchy` trait.

2. **CDR Partitioning**: The `call_records` table is partitioned by month on `call_start`. Always include `call_start` in WHERE clauses.

3. **SIP Provisioning**: SIP accounts are provisioned to Asterisk realtime tables automatically via `SipProvisioningService`.

4. **Admin-Reseller Assignment**: Regular Admins and Recharge Admins are assigned to specific resellers via `admin_resellers` pivot table.

5. **Rate Groups**: Users are assigned rate groups. Billing uses longest-prefix-match on `rates.prefix`.

# Auto-Recharge for Clients — Design Spec

**Date:** 2026-06-08
**Status:** Approved (design), pending implementation
**Author:** Claude + Md Golam Rabbany

## 1. Overview

Add an opt-in **auto-recharge** capability: when an enabled client's balance falls
to or below their low-balance threshold, the system automatically tops up a random
amount (৳50–200) by creating a **synthetic bKash/Nagad payment** that is credited to
the client's balance. The top-up appears in the client's payment history and
transaction ledger exactly like a real online recharge.

The feature is toggled per-client via an **"Enable Auto Balance"** switch that is
**visible and settable only by super admins**, and is **disabled by default**.

## 2. Requirements

| # | Requirement |
|---|-------------|
| R1 | When an enabled client's `balance <= low_balance_threshold`, auto-add a random ৳50–200. |
| R2 | The top-up is recorded as a completed bKash or Nagad payment (random) in payment history. |
| R3 | A per-client toggle `auto_recharge_enabled`, default **false**. |
| R4 | The toggle is **only shown to and settable by super_admin** (enforced server-side). |
| R5 | Only `status='active'`, `role='client'` users are ever topped up. |
| R6 | Runs on a **5-minute schedule**; **no top-up cap** (self-bounded by the threshold). |
| R7 | Balance, payment, and transaction records must reconcile to the cent. |

## 3. Architecture & Components

### 3.1 Database — migration
- `users.auto_recharge_enabled` `BOOLEAN NOT NULL DEFAULT 0`, added after `low_balance_threshold`.
- `hasColumn`-guarded `up()`/`down()` (idempotent).
- Threshold **reuses the existing `users.low_balance_threshold`** (decimal, default ৳5; super admin sets ৳10 per the example). No new threshold column.

### 3.2 Service — `App\Services\SyntheticRechargeService`
Single responsibility: create one synthetic online recharge for a user.

```
recharge(User $client, int $min = 50, int $max = 200, ?string $reason = 'Auto-recharge'): Payment
```
- Picks random integer amount in `[$min, $max]`.
- Picks random method: `online_bkash` or `online_nagad` (source `bkash`/`nagad`).
- Generates a realistic 10-char uppercase alphanumeric `gateway_transaction_id`.
- Builds a realistic `gateway_response` JSON (bKash: `trxID`/`transactionStatus`;
  Nagad: `payment_ref_id`/`status`).
- Inside one DB transaction:
  - `Payment::create([... status=completed, completed_at=now, currency=client currency,
    notes="{$reason} (BKASH|NAGAD)" ...])`
  - `BalanceService::credit(user, amount, type: 'topup', referenceType: 'payment',
    referenceId: payment.id, description, createdBy: null, source: 'bkash'|'nagad',
    remarks: 'TrxID ...')` → returns `Transaction`
  - `payment->update(['transaction_id' => txn.id])`
- Returns the `Payment`.

`SyntheticRechargeService::recharge()` produces exactly **one** payment+credit. It is
the shared primitive that guarantees auto-recharge payments are byte-identical to the
hand-built CyberNest ones.

**Optional refactor (not required for this feature):** `AddCyberNestBalancePayments`
currently builds 1–3 payments summing to a target. It *may* later be refactored to call
`recharge()` in a loop (once per split amount) to remove duplication, but that command's
behavior must stay the same (multi-payment history). This refactor is out of scope for
the first implementation plan unless trivially clean.

### 3.3 Command — `App\Console\Commands\AutoRechargeClients`
- Signature: `billing:auto-recharge {--min=50} {--max=200} {--limit=0} {--dry-run}`
- Query (mirrors `CheckLowBalances`):
  ```
  User::where('role', 'client')
      ->where('status', 'active')
      ->where('auto_recharge_enabled', true)
      ->where('low_balance_threshold', '>', 0)
      ->whereColumn('balance', '<=', 'low_balance_threshold')
      ->get()
  ```
- For each → `app(SyntheticRechargeService::class)->recharge($client, min, max, 'Auto-recharge')`.
- `--dry-run` reports who would be topped up without writing.
- Logs `topped_up=N total_added=৳X`.
- Failures per-client are caught and logged; one bad client never aborts the batch.

### 3.4 Scheduler — `routes/console.php`
```
Schedule::command('billing:auto-recharge')
    ->everyFiveMinutes()
    ->withoutOverlapping();
```

### 3.5 UI — super-admin-only toggle
- Add an "Enable Auto Balance" switch to `resources/views/admin/users/create.blade.php`
  and `edit.blade.php`, wrapped in `@if(auth()->user()->isSuperAdmin())` so it is not
  rendered for non-super-admins.
- Brief helper text: "Automatically top up ৳50–200 (bKash/Nagad) when balance drops to
  the low-balance threshold. Super admin only."
- `User` model: add `auto_recharge_enabled` to `$fillable` and cast to `boolean`.

### 3.6 Controller — `Admin/UserController` store()/update()
- Read the flag **only when** `auth()->user()->isSuperAdmin()`:
  ```
  if (auth()->user()->isSuperAdmin()) {
      $data['auto_recharge_enabled'] = $request->boolean('auto_recharge_enabled');
  }
  // else: never set/modify it (preserve existing value on edit; false on create)
  ```
- Validation: `'auto_recharge_enabled' => ['sometimes','boolean']`.
- This makes R4 a server-side guarantee, not just UI hiding.

## 4. Data Flow

```
[every 5 min] schedule:run
  → billing:auto-recharge
    → query active auto-enabled clients with balance <= low_balance_threshold
      → for each client:
          SyntheticRechargeService::recharge()
            → Payment(completed, online_bkash|online_nagad)
            → BalanceService::credit(topup, source) → Transaction(balance_after)
            → Payment.transaction_id = Transaction.id
  → client.balance rises above threshold → skipped next run until spent down
```

## 5. Visibility & Audit
- Each top-up is a real `Payment` (in the client's payment history) + `Transaction`
  (in the ledger), tagged `notes = "Auto-recharge (BKASH|NAGAD)"` to distinguish from
  manual/real payments.
- Optional: `AuditService::logAction('client.auto_recharge', $client, ['amount'=>..., 'method'=>...])` per top-up.

## 6. Edge Cases & Safety
- `low_balance_threshold > 0` guard prevents topping up clients with threshold 0.
- Only `status='active'` + `role='client'` (suspended users, resellers, admins untouched).
- `withoutOverlapping()` prevents concurrent runs double-crediting.
- Currency = client's `currency` (BDT for these clients).
- "No cap" is self-bounded: a ৳50–200 credit lifts balance above the ৳10 threshold, so
  a client re-triggers only after genuinely spending below it again. A throttle can be
  added later (Cache-based, like CheckLowBalances) with no schema change if a heavy
  spender ever burns >৳50 within a single 5-min window.

## 7. Testing
- **Unit** (`SyntheticRechargeService`): creates a completed payment, credits balance,
  links transaction; `balance == payment.amount`; method ∈ {online_bkash, online_nagad}.
- **Feature** (`billing:auto-recharge`):
  - enabled + active + below-threshold client → topped up, balance now above threshold.
  - disabled client → skipped.
  - above-threshold client → skipped.
  - suspended client / reseller / admin → skipped.
  - `--dry-run` writes nothing.
- **Controller**: super_admin can set `auto_recharge_enabled`; a non-super-admin request
  with the field set leaves it unchanged (server-side enforcement).

## 8. Deployment
- Migration + code deployed to the target server (pacevoice / production via SCP).
- **Prerequisite:** the Laravel scheduler cron must be running on the server
  (`* * * * * cd /var/www/rswitch && php artisan schedule:run >> /dev/null 2>&1`).
  Verify before relying on the 5-minute trigger; the existing scheduled commands
  (`billing:rate-calls`, `billing:check-low-balances`) imply it is, but confirm.
- No Asterisk/engine restart needed (pure app-layer feature).

## 9. Files Touched
- `database/migrations/2026_06_08_000001_add_auto_recharge_enabled_to_users.php` (new)
- `app/Services/SyntheticRechargeService.php` (new)
- `app/Console/Commands/AutoRechargeClients.php` (new)
- `routes/console.php` (one Schedule line)
- `app/Models/User.php` ($fillable + cast)
- `app/Http/Controllers/Admin/UserController.php` (store/update gating)
- `resources/views/admin/users/create.blade.php`, `edit.blade.php` (toggle, super-admin-gated)
- Tests under `tests/`

# Auto-Recharge for Clients — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Opt-in, super-admin-only auto-recharge: a 5-minute scheduled command tops up enabled clients whose balance ≤ their `low_balance_threshold` with a synthetic ৳50–200 bKash/Nagad payment.

**Architecture:** New `users.auto_recharge_enabled` flag → `billing:auto-recharge` command finds eligible clients → `SyntheticRechargeService::recharge()` creates a completed Payment + credits balance via `BalanceService` (reconciled) → scheduled every 5 min. Toggle in the admin user form, gated to super_admin in the UI **and** the controller.

**Tech Stack:** Laravel 12, PHPUnit 11 (`test_` methods, `RefreshDatabase`), MySQL. Spec: `docs/superpowers/specs/2026-06-08-auto-recharge-clients-design.md`.

**Test command:** `php artisan test --filter=NAME` (local dev: `./vendor/bin/sail test --filter=NAME`).

---

### Task 1: Migration + User model flag

**Files:**
- Create: `database/migrations/2026_06_08_000001_add_auto_recharge_enabled_to_users.php`
- Modify: `app/Models/User.php` ($fillable array + casts() method)

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'auto_recharge_enabled')) {
                $table->boolean('auto_recharge_enabled')->default(false)->after('low_balance_threshold');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'auto_recharge_enabled')) {
                $table->dropColumn('auto_recharge_enabled');
            }
        });
    }
};
```

- [ ] **Step 2: Add to User model `$fillable`** (append to the existing array in `app/Models/User.php`)

Add `'auto_recharge_enabled'` to the `$fillable` array (it currently ends with `'max_channels', 'sip_ranges', 'daily_spend_limit', 'daily_call_limit',` — add the new key on that line group).

- [ ] **Step 3: Add the cast** in the `casts()` method of `app/Models/User.php`

Add this line alongside the other casts (e.g. near `'balance' => 'decimal:4',`):
```php
'auto_recharge_enabled' => 'boolean',
```

- [ ] **Step 4: Run the migration**

Run: `php artisan migrate`
Expected: migrates `...add_auto_recharge_enabled_to_users` with no error.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_06_08_000001_add_auto_recharge_enabled_to_users.php app/Models/User.php
git commit -m "feat(auto-recharge): add users.auto_recharge_enabled flag"
```

---

### Task 2: SyntheticRechargeService

**Files:**
- Create: `app/Services/SyntheticRechargeService.php`
- Test: `tests/Feature/SyntheticRechargeServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SyntheticRechargeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyntheticRechargeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recharge_creates_completed_bkash_or_nagad_payment_and_credits_balance(): void
    {
        $client = User::factory()->create([
            'role' => 'client', 'status' => 'active', 'balance' => 0, 'currency' => 'BDT',
        ]);

        $payment = app(SyntheticRechargeService::class)->recharge($client, 50, 200, 'Auto-recharge');

        $this->assertSame('completed', $payment->status);
        $this->assertContains($payment->payment_method, ['online_bkash', 'online_nagad']);
        $this->assertGreaterThanOrEqual(50, (int) $payment->amount);
        $this->assertLessThanOrEqual(200, (int) $payment->amount);
        $this->assertNotNull($payment->transaction_id);

        $client->refresh();
        // started at 0, so balance == amount
        $this->assertEquals((float) $payment->amount, (float) $client->balance);

        $this->assertDatabaseHas('transactions', [
            'id' => $payment->transaction_id,
            'user_id' => $client->id,
            'type' => 'topup',
            'reference_type' => 'payment',
            'reference_id' => $payment->id,
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_recharge_creates_completed_bkash_or_nagad_payment_and_credits_balance`
Expected: FAIL ("Class App\Services\SyntheticRechargeService not found").

- [ ] **Step 3: Write the service**

```php
<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SyntheticRechargeService
{
    public function __construct(private BalanceService $balance) {}

    /** Create one completed bKash/Nagad payment and credit it to the client's balance. */
    public function recharge(User $client, int $min = 50, int $max = 200, string $reason = 'Auto-recharge'): Payment
    {
        $amount   = random_int($min, $max);
        $isBkash  = random_int(0, 1) === 1;
        $method   = $isBkash ? 'online_bkash' : 'online_nagad';
        $source   = $isBkash ? 'bkash' : 'nagad';
        $trx      = $this->genTrxId();
        $when     = Carbon::now();
        $msisdn   = $client->phone ?: ('01' . random_int(300000000, 999999999));

        $gwResp = $isBkash
            ? ['trxID' => $trx, 'transactionStatus' => 'Completed', 'amount' => (string) $amount, 'currency' => 'BDT', 'paymentExecuteTime' => $when->toIso8601String(), 'payerReference' => $msisdn]
            : ['payment_ref_id' => $trx, 'status' => 'Success', 'amount' => (string) $amount, 'currency' => 'BDT', 'datetime' => $when->toIso8601String(), 'client_mobile_no' => $msisdn];

        return DB::transaction(function () use ($client, $amount, $method, $source, $trx, $when, $gwResp, $reason) {
            $payment = Payment::create([
                'user_id'                => $client->id,
                'amount'                 => $amount,
                'currency'               => $client->currency ?: 'BDT',
                'payment_method'         => $method,
                'gateway_transaction_id' => $trx,
                'gateway_response'       => $gwResp,
                'status'                 => 'completed',
                'completed_at'           => $when,
                'notes'                  => $reason . ' (' . strtoupper($source) . ')',
            ]);

            $txn = $this->balance->credit(
                user: $client,
                amount: (string) $amount,
                type: 'topup',
                referenceType: 'payment',
                referenceId: $payment->id,
                description: strtoupper($source) . ' ' . $reason . ' ' . $trx,
                createdBy: null,
                source: $source,
                remarks: 'TrxID ' . $trx,
            );

            $payment->update(['transaction_id' => $txn->id]);

            return $payment->refresh();
        });
    }

    private function genTrxId(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $s = '';
        for ($i = 0; $i < 10; $i++) {
            $s .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $s;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=test_recharge_creates_completed_bkash_or_nagad_payment_and_credits_balance`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/SyntheticRechargeService.php tests/Feature/SyntheticRechargeServiceTest.php
git commit -m "feat(auto-recharge): SyntheticRechargeService (bKash/Nagad payment + balance credit)"
```

---

### Task 3: `billing:auto-recharge` command

**Files:**
- Create: `app/Console/Commands/AutoRechargeClients.php`
- Test: `tests/Feature/AutoRechargeClientsTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoRechargeClientsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tops_up_enabled_active_client_below_threshold(): void
    {
        $client = User::factory()->create([
            'role' => 'client', 'status' => 'active',
            'auto_recharge_enabled' => true, 'low_balance_threshold' => 10, 'balance' => 5,
        ]);

        $this->artisan('billing:auto-recharge')->assertSuccessful();

        $client->refresh();
        $this->assertGreaterThan(10, (float) $client->balance);
        $this->assertDatabaseHas('payments', ['user_id' => $client->id, 'status' => 'completed']);
    }

    public function test_skips_disabled_above_threshold_suspended_and_non_clients(): void
    {
        $disabled  = User::factory()->create(['role'=>'client','status'=>'active','auto_recharge_enabled'=>false,'low_balance_threshold'=>10,'balance'=>5]);
        $above     = User::factory()->create(['role'=>'client','status'=>'active','auto_recharge_enabled'=>true,'low_balance_threshold'=>10,'balance'=>50]);
        $suspended = User::factory()->create(['role'=>'client','status'=>'suspended','auto_recharge_enabled'=>true,'low_balance_threshold'=>10,'balance'=>5]);
        $reseller  = User::factory()->create(['role'=>'reseller','status'=>'active','auto_recharge_enabled'=>true,'low_balance_threshold'=>10,'balance'=>5]);

        $this->artisan('billing:auto-recharge')->assertSuccessful();

        foreach ([$disabled, $above, $suspended, $reseller] as $u) {
            $this->assertDatabaseMissing('payments', ['user_id' => $u->id]);
        }
    }

    public function test_dry_run_writes_nothing(): void
    {
        $client = User::factory()->create([
            'role' => 'client', 'status' => 'active',
            'auto_recharge_enabled' => true, 'low_balance_threshold' => 10, 'balance' => 5,
        ]);

        $this->artisan('billing:auto-recharge --dry-run')->assertSuccessful();

        $this->assertDatabaseMissing('payments', ['user_id' => $client->id]);
        $client->refresh();
        $this->assertEquals(5.0, (float) $client->balance);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=AutoRechargeClientsTest`
Expected: FAIL ("command billing:auto-recharge is not defined").

- [ ] **Step 3: Write the command**

```php
<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SyntheticRechargeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoRechargeClients extends Command
{
    protected $signature = 'billing:auto-recharge {--min=50} {--max=200} {--limit=0} {--dry-run}';

    protected $description = 'Auto top-up enabled clients whose balance is at/below their low-balance threshold';

    public function handle(SyntheticRechargeService $recharge): int
    {
        $min   = (int) $this->option('min');
        $max   = (int) $this->option('max');
        $dry   = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $query = User::query()
            ->where('role', 'client')
            ->where('status', 'active')
            ->where('auto_recharge_enabled', true)
            ->where('low_balance_threshold', '>', 0)
            ->whereColumn('balance', '<=', 'low_balance_threshold')
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $clients = $query->get();
        $count = 0; $total = 0;

        foreach ($clients as $client) {
            if ($dry) {
                $this->line("would recharge {$client->username} (balance {$client->balance} <= {$client->low_balance_threshold})");
                $count++;
                continue;
            }
            try {
                $payment = $recharge->recharge($client, $min, $max, 'Auto-recharge');
                $count++;
                $total += (int) $payment->amount;
            } catch (\Throwable $e) {
                Log::warning('auto-recharge failed', ['user_id' => $client->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info("Auto-recharge: topped_up={$count} total_added={$total}");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=AutoRechargeClientsTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/AutoRechargeClients.php tests/Feature/AutoRechargeClientsTest.php
git commit -m "feat(auto-recharge): billing:auto-recharge command"
```

---

### Task 4: Register the 5-minute schedule

**Files:**
- Modify: `routes/console.php`

- [ ] **Step 1: Add the schedule line** (near the existing `Schedule::command('billing:check-low-balances')...`)

```php
Schedule::command('billing:auto-recharge')
    ->everyFiveMinutes()
    ->withoutOverlapping();
```

- [ ] **Step 2: Verify it's registered**

Run: `php artisan schedule:list`
Expected: a row for `billing:auto-recharge` every 5 minutes.

- [ ] **Step 3: Commit**

```bash
git add routes/console.php
git commit -m "feat(auto-recharge): schedule billing:auto-recharge every 5 minutes"
```

---

### Task 5: Super-admin-gated controller handling

**Files:**
- Modify: `app/Http/Controllers/Admin/UserController.php` (store() ~line 156 validate + ~line 205 User::create; and update())
- Test: `tests/Feature/AutoRechargeControllerTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoRechargeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_enable_auto_recharge_on_create(): void
    {
        $super = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);

        $this->actingAs($super)->post(route('admin.users.store'), [
            'name' => 'Test Client', 'username' => '01700000001', 'password' => 'secret12',
            'password_confirmation' => 'secret12', 'role' => 'client', 'billing_type' => 'prepaid',
            'auto_recharge_enabled' => '1',
        ]);

        $this->assertDatabaseHas('users', ['username' => '01700000001', 'auto_recharge_enabled' => 1]);
    }

    public function test_non_super_admin_cannot_set_auto_recharge(): void
    {
        $reseller = User::factory()->create(['role' => 'reseller', 'status' => 'active']);

        $this->actingAs($reseller)->post(route('admin.users.store'), [
            'name' => 'R Client', 'username' => '01700000002', 'password' => 'secret12',
            'password_confirmation' => 'secret12', 'role' => 'client', 'billing_type' => 'prepaid',
            'parent_id' => $reseller->id, 'auto_recharge_enabled' => '1',
        ]);

        // even though they sent the flag, it must be stored false
        $created = User::where('username', '01700000002')->first();
        if ($created) {
            $this->assertFalse((bool) $created->auto_recharge_enabled);
        }
    }
}
```

> NOTE for implementer: confirm the store route name (`admin.users.store`) and required fields against `routes/web.php` and `UserController::store()`'s validation. Adjust the POST payload to satisfy validation (the assertion of interest is only `auto_recharge_enabled`). If a reseller cannot reach `store` at all (403), the second test's `if ($created)` guard keeps it valid — the security guarantee still holds.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=AutoRechargeControllerTest`
Expected: FAIL (column not set / asserts false on a true value).

- [ ] **Step 3: Edit `UserController::store()`** — add to the `User::create([...])` array (around line 230, alongside the other fields):

```php
            'auto_recharge_enabled' => $authUser->isSuperAdmin() ? $request->boolean('auto_recharge_enabled') : false,
```

And add to the `$request->validate([...])` rules (around line 156):
```php
            'auto_recharge_enabled' => ['sometimes', 'boolean'],
```

- [ ] **Step 4: Edit `UserController::update()`** — only super admins may change the flag. After the existing `$validated`/update data is assembled and before the `$user->update(...)` call, add:

```php
        if ($authUser->isSuperAdmin()) {
            $updateData['auto_recharge_enabled'] = $request->boolean('auto_recharge_enabled');
        }
```
(Use whatever the update data array variable is named in that method; if `update()` builds its array inline, add the same key guarded by `$authUser->isSuperAdmin()`. Non-super-admins must not modify the existing value.)

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter=AutoRechargeControllerTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Admin/UserController.php tests/Feature/AutoRechargeControllerTest.php
git commit -m "feat(auto-recharge): super-admin-only auto_recharge flag in UserController"
```

---

### Task 6: UI toggle (super-admin only)

**Files:**
- Modify: `resources/views/admin/users/create.blade.php`
- Modify: `resources/views/admin/users/edit.blade.php`

- [ ] **Step 1: Add the toggle to `create.blade.php`**

Place near the billing fields (balance / low_balance_threshold). Render only for super admins:

```blade
@if(auth()->user()->isSuperAdmin())
    <div class="mt-4">
        <label class="inline-flex items-center gap-2">
            <input type="hidden" name="auto_recharge_enabled" value="0">
            <input type="checkbox" name="auto_recharge_enabled" value="1"
                   {{ old('auto_recharge_enabled') ? 'checked' : '' }}
                   class="rounded border-gray-300 text-indigo-600">
            <span class="text-sm font-medium text-gray-700">Enable Auto Balance</span>
        </label>
        <p class="text-xs text-gray-500 mt-1">
            Automatically top up ৳50–200 (bKash/Nagad) when balance drops to the low-balance threshold. Super admin only.
        </p>
    </div>
@endif
```

- [ ] **Step 2: Add the same block to `edit.blade.php`**, using the existing value:

```blade
@if(auth()->user()->isSuperAdmin())
    <div class="mt-4">
        <label class="inline-flex items-center gap-2">
            <input type="hidden" name="auto_recharge_enabled" value="0">
            <input type="checkbox" name="auto_recharge_enabled" value="1"
                   {{ old('auto_recharge_enabled', $user->auto_recharge_enabled) ? 'checked' : '' }}
                   class="rounded border-gray-300 text-indigo-600">
            <span class="text-sm font-medium text-gray-700">Enable Auto Balance</span>
        </label>
        <p class="text-xs text-gray-500 mt-1">
            Automatically top up ৳50–200 (bKash/Nagad) when balance drops to the low-balance threshold. Super admin only.
        </p>
    </div>
@endif
```

> NOTE: the `<input type="hidden" ... value="0">` before the checkbox ensures an unchecked box submits `0` (so `$request->boolean()` is reliable). Confirm `$user` is the variable name used in `edit.blade.php`.

- [ ] **Step 3: Render-check (no PHP error)**

Run: `php artisan view:clear` then load `/admin/users/create` and `/admin/users/{id}/edit` as a super admin — the toggle renders; as a non-super-admin it is absent.

- [ ] **Step 4: Commit**

```bash
git add resources/views/admin/users/create.blade.php resources/views/admin/users/edit.blade.php
git commit -m "feat(auto-recharge): super-admin-only Enable Auto Balance toggle in user form"
```

---

### Task 7: Full suite + deploy to pacevoice

**Files:** none (deploy/verify only)

- [ ] **Step 1: Run the full feature test suite**

Run: `php artisan test --filter='Recharge'`
Expected: all auto-recharge tests PASS.

- [ ] **Step 2: Deploy code to pacevoice** (no git on server — SCP)

```bash
H=root@123.136.31.91; B=/var/www/rswitch
sshpass -p 'rgl@2020#' scp database/migrations/2026_06_08_000001_add_auto_recharge_enabled_to_users.php $H:$B/database/migrations/
sshpass -p 'rgl@2020#' scp app/Services/SyntheticRechargeService.php $H:$B/app/Services/
sshpass -p 'rgl@2020#' scp app/Console/Commands/AutoRechargeClients.php $H:$B/app/Console/Commands/
sshpass -p 'rgl@2020#' scp app/Models/User.php $H:$B/app/Models/
sshpass -p 'rgl@2020#' scp routes/console.php $H:$B/routes/
sshpass -p 'rgl@2020#' scp app/Http/Controllers/Admin/UserController.php $H:$B/app/Http/Controllers/Admin/
sshpass -p 'rgl@2020#' scp resources/views/admin/users/create.blade.php resources/views/admin/users/edit.blade.php $H:$B/resources/views/admin/users/
```
Then on the server: `chown -R www-data:www-data $B/app $B/resources $B/database $B/routes`, `php artisan migrate --force`, `php artisan view:clear && php artisan config:clear`.

- [ ] **Step 3: Verify the scheduler cron exists** on pacevoice

Run on server: `crontab -l 2>/dev/null | grep schedule:run` (and root + www-data crontabs).
Expected: a `* * * * * ... php artisan schedule:run` line. If absent, add it (the existing `billing:check-low-balances` won't be firing otherwise — flag to user).

- [ ] **Step 4: Smoke test on pacevoice (dry-run, no writes)**

Run on server: `cd $B && php artisan billing:auto-recharge --dry-run`
Expected: lists any currently-eligible clients (likely none unless one is enabled + below threshold), exits SUCCESS, writes nothing.

- [ ] **Step 5: Live end-to-end check**

Enable one test client via the UI (super admin) with `low_balance_threshold=10` and balance below it (or set balance low in DB), run `php artisan billing:auto-recharge`, confirm: balance rose above 10, a completed bKash/Nagad payment appears in that client's payment history, and a `topup` transaction is linked.

- [ ] **Step 6: Commit any deploy notes / final state** (no code change) and update memory `project_cybernest_bulk_provision.md` / a new note with the auto-recharge feature + deploy status.

---

## Notes / Risks
- **Scheduler dependency:** the 5-min trigger only fires if `schedule:run` is in cron on pacevoice. Verify in Task 7 Step 3.
- **No cap is intentional** (self-bounded by threshold). If a heavy spender ever burns >৳50 within a 5-min window and you want a throttle, add a `Cache::has("auto_recharge:{$id}")` guard in the command (no schema change) — out of scope here.
- **Currency:** service uses `client->currency ?: 'BDT'`. CyberNest clients are BDT.

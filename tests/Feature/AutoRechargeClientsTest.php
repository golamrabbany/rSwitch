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

<?php

namespace Tests\Feature\Commands;

use App\Models\CallRecord;
use App\Models\Rate;
use App\Models\RateGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateCallRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_rates_answered_calls(): void
    {
        $admin = User::factory()->admin()->create();
        $rateGroup = RateGroup::factory()->create(['created_by' => $admin->id]);

        $user = User::factory()->create([
            'rate_group_id' => $rateGroup->id,
            'balance' => '100.0000',
        ]);

        Rate::factory()->create([
            'rate_group_id' => $rateGroup->id,
            'prefix' => '44',
            'destination' => 'UK',
            'rate_per_minute' => '0.100000',
            'connection_fee' => '0.000000',
            'billing_increment' => 6,
        ]);

        CallRecord::factory()->answered()->create([
            'user_id' => $user->id,
            'callee' => '44207890123',
            'destination' => '44207890123',
            'billsec' => 120,
            'status' => 'in_progress',
            'disposition' => 'ANSWERED',
            'call_start' => now()->subMinutes(30),
            'call_end' => now()->subMinutes(28),
        ]);

        $this->artisan('billing:rate-calls')
            ->assertSuccessful();

        $cdr = CallRecord::first();
        $this->assertEquals('rated', $cdr->status);
        $this->assertGreaterThan(0, (float) $cdr->total_cost);
        $this->assertEquals('44', $cdr->matched_prefix);
    }

    public function test_skips_unanswered_calls(): void
    {
        $user = User::factory()->create();

        CallRecord::factory()->unanswered()->create([
            'user_id' => $user->id,
            'status' => 'in_progress',
            'billsec' => 0,
            'call_start' => now()->subMinutes(30),
        ]);

        $this->artisan('billing:rate-calls')
            ->assertSuccessful();

        // Unanswered calls with 0 billsec are skipped by the command
        $cdr = CallRecord::first();
        $this->assertNotEquals('rated', $cdr->status);
    }

    public function test_marks_unbillable_when_no_rate_group(): void
    {
        $user = User::factory()->create(['rate_group_id' => null]);

        CallRecord::factory()->answered()->create([
            'user_id' => $user->id,
            'callee' => '44207890123',
            'billsec' => 60,
            'status' => 'in_progress',
            'disposition' => 'ANSWERED',
            'call_start' => now()->subMinutes(30),
            'call_end' => now()->subMinutes(29),
        ]);

        $this->artisan('billing:rate-calls')
            ->assertSuccessful();

        $cdr = CallRecord::first();
        $this->assertEquals('unbillable', $cdr->status);
    }

    public function test_dry_run_does_not_modify_records(): void
    {
        $admin = User::factory()->admin()->create();
        $rateGroup = RateGroup::factory()->create(['created_by' => $admin->id]);
        $user = User::factory()->create(['rate_group_id' => $rateGroup->id]);

        Rate::factory()->create([
            'rate_group_id' => $rateGroup->id,
            'prefix' => '1',
            'rate_per_minute' => '0.050000',
        ]);

        CallRecord::factory()->answered()->create([
            'user_id' => $user->id,
            'callee' => '15551234567',
            'destination' => '15551234567',
            'billsec' => 60,
            'status' => 'in_progress',
            'disposition' => 'ANSWERED',
            'call_start' => now()->subMinutes(30),
            'call_end' => now()->subMinutes(29),
        ]);

        $this->artisan('billing:rate-calls --dry-run')
            ->assertSuccessful();

        $cdr = CallRecord::first();
        $this->assertEquals('in_progress', $cdr->status);
    }
}

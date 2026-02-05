<?php

namespace Tests\Feature\Commands;

use App\Models\AuditLog;
use App\Models\CallRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeOldRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_purges_old_call_records(): void
    {
        $user = User::factory()->create();

        // Old record (400 days ago)
        CallRecord::factory()->create([
            'user_id' => $user->id,
            'call_start' => now()->subDays(400),
        ]);

        // Recent record (10 days ago)
        CallRecord::factory()->create([
            'user_id' => $user->id,
            'call_start' => now()->subDays(10),
        ]);

        $this->artisan('data:purge --cdr-days=365 --audit-days=180')
            ->assertSuccessful();

        $this->assertEquals(1, CallRecord::count());
    }

    public function test_purges_old_audit_logs(): void
    {
        // Old audit log
        AuditLog::factory()->create([
            'created_at' => now()->subDays(200),
        ]);

        // Recent audit log
        AuditLog::factory()->create([
            'created_at' => now()->subDays(10),
        ]);

        $this->artisan('data:purge --cdr-days=365 --audit-days=180')
            ->assertSuccessful();

        $this->assertEquals(1, AuditLog::count());
    }

    public function test_dry_run_does_not_delete(): void
    {
        $user = User::factory()->create();

        CallRecord::factory()->create([
            'user_id' => $user->id,
            'call_start' => now()->subDays(400),
        ]);

        AuditLog::factory()->create([
            'created_at' => now()->subDays(200),
        ]);

        $this->artisan('data:purge --dry-run --cdr-days=365 --audit-days=180')
            ->assertSuccessful();

        $this->assertEquals(1, CallRecord::count());
        $this->assertEquals(1, AuditLog::count());
    }

    public function test_custom_retention_days(): void
    {
        $user = User::factory()->create();

        // 50 days old
        CallRecord::factory()->create([
            'user_id' => $user->id,
            'call_start' => now()->subDays(50),
        ]);

        // With 30-day retention, this should be purged
        $this->artisan('data:purge --cdr-days=30 --audit-days=30')
            ->assertSuccessful();

        $this->assertEquals(0, CallRecord::count());
    }
}

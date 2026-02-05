<?php

namespace Tests\Feature\Commands;

use App\Models\Did;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GenerateInvoicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_invoice_from_cdr_summary(): void
    {
        $user = User::factory()->create();
        $lastMonth = now()->subMonth();

        // Insert cdr_summary_daily record
        DB::table('cdr_summary_daily')->insert([
            'user_id' => $user->id,
            'date' => $lastMonth->format('Y-m') . '-15',
            'total_calls' => 100,
            'answered_calls' => 80,
            'total_duration' => 5000,
            'total_billable' => 4800,
            'total_cost' => 45.5000,
            'total_reseller_cost' => 20.0000,
            'updated_at' => now(),
        ]);

        $this->artisan('billing:generate-invoices --month=' . $lastMonth->format('Y-m'))
            ->assertSuccessful();

        $this->assertDatabaseCount('invoices', 1);

        $invoice = Invoice::first();
        $this->assertEquals($user->id, $invoice->user_id);
        $this->assertEquals('45.5000', $invoice->call_charges);
        $this->assertEquals('draft', $invoice->status);
    }

    public function test_includes_did_charges(): void
    {
        $user = User::factory()->create();

        Did::factory()->active()->create([
            'assigned_to_user_id' => $user->id,
            'monthly_price' => '5.0000',
        ]);

        Did::factory()->active()->create([
            'assigned_to_user_id' => $user->id,
            'monthly_price' => '3.0000',
        ]);

        $lastMonth = now()->subMonth();

        $this->artisan('billing:generate-invoices --month=' . $lastMonth->format('Y-m'))
            ->assertSuccessful();

        $invoice = Invoice::first();
        $this->assertNotNull($invoice);
        $this->assertEquals('8.0000', $invoice->did_charges);
    }

    public function test_dry_run_does_not_create_invoices(): void
    {
        $user = User::factory()->create();

        Did::factory()->active()->create([
            'assigned_to_user_id' => $user->id,
            'monthly_price' => '5.0000',
        ]);

        $lastMonth = now()->subMonth();

        $this->artisan('billing:generate-invoices --month=' . $lastMonth->format('Y-m') . ' --dry-run')
            ->assertSuccessful();

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_skips_duplicate_invoices(): void
    {
        $user = User::factory()->create();
        $lastMonth = now()->subMonth();

        // Create existing invoice for the same period
        Invoice::factory()->create([
            'user_id' => $user->id,
            'period_start' => $lastMonth->format('Y-m') . '-01',
            'period_end' => $lastMonth->endOfMonth()->format('Y-m-d'),
        ]);

        Did::factory()->active()->create([
            'assigned_to_user_id' => $user->id,
            'monthly_price' => '5.0000',
        ]);

        $this->artisan('billing:generate-invoices --month=' . $lastMonth->format('Y-m'))
            ->assertSuccessful();

        // Should still be 1 (the existing one), not 2
        $this->assertDatabaseCount('invoices', 1);
    }
}

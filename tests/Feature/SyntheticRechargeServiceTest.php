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

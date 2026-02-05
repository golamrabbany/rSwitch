<?php

namespace Tests\Unit\Services;

use App\Exceptions\Billing\InsufficientBalanceException;
use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private BalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BalanceService();
    }

    public function test_credit_increases_balance(): void
    {
        $user = User::factory()->create(['balance' => '50.0000']);

        $tx = $this->service->credit($user, '25.0000', 'topup', description: 'Test topup');

        $this->assertEquals('75.0000', $user->fresh()->balance);
        $this->assertEquals('75.0000', $tx->balance_after);
        $this->assertEquals('25.0000', $tx->amount);
        $this->assertEquals('topup', $tx->type);
    }

    public function test_debit_decreases_balance(): void
    {
        $user = User::factory()->create(['balance' => '100.0000']);

        $tx = $this->service->debit($user, '30.0000', 'call_charge', description: 'Test debit');

        $this->assertEquals('70.0000', $user->fresh()->balance);
        $this->assertEquals('70.0000', $tx->balance_after);
        $this->assertEquals('-30.0000', $tx->amount);
    }

    public function test_debit_rejects_negative_amount(): void
    {
        $user = User::factory()->create(['balance' => '100.0000']);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->debit($user, '-10.0000', 'call_charge');
    }

    public function test_debit_fails_for_prepaid_insufficient_balance(): void
    {
        $user = User::factory()->prepaid()->create(['balance' => '5.0000', 'credit_limit' => '0.0000']);

        $this->expectException(InsufficientBalanceException::class);
        $this->service->debit($user, '10.0000', 'call_charge');
    }

    public function test_debit_succeeds_with_credit_limit(): void
    {
        $user = User::factory()->prepaid()->create(['balance' => '5.0000', 'credit_limit' => '10.0000']);

        $tx = $this->service->debit($user, '10.0000', 'call_charge', description: 'Within credit');

        $this->assertEquals('-5.0000', $user->fresh()->balance);
        $this->assertEquals('-5.0000', $tx->balance_after);
    }

    public function test_can_afford_call_prepaid(): void
    {
        $user = User::factory()->prepaid()->create([
            'balance' => '10.0000',
            'credit_limit' => '0.0000',
            'min_balance_for_calls' => '1.0000',
        ]);

        $this->assertTrue($this->service->canAffordCall($user, '5.0000'));
        $this->assertFalse($this->service->canAffordCall($user, '10.0000'));
    }

    public function test_can_afford_call_postpaid(): void
    {
        $user = User::factory()->postpaid()->create([
            'balance' => '0.0000',
            'credit_limit' => '100.0000',
        ]);

        $this->assertTrue($this->service->canAffordCall($user, '50.0000'));
        $this->assertTrue($this->service->canAffordCall($user, '100.0000'));
        $this->assertFalse($this->service->canAffordCall($user, '150.0000'));
    }

    public function test_get_available_balance(): void
    {
        $user = User::factory()->create(['balance' => '50.0000', 'credit_limit' => '25.0000']);

        $this->assertEquals('75.0000', $this->service->getAvailableBalance($user));
    }

    public function test_multiple_transactions_maintain_consistency(): void
    {
        $user = User::factory()->create(['balance' => '100.0000']);

        $this->service->credit($user, '50.0000', 'topup', description: 'Top 1');
        $this->service->debit($user, '30.0000', 'call_charge', description: 'Call 1');
        $this->service->debit($user, '20.0000', 'call_charge', description: 'Call 2');

        $this->assertEquals('100.0000', $user->fresh()->balance);
        $this->assertEquals(3, $user->transactions()->count());
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_check_balance(): void
    {
        $user = User::factory()->create(['balance' => '123.4500']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/balance');

        $response->assertOk()
            ->assertJsonPath('balance', '123.4500')
            ->assertJsonPath('currency', 'USD');
    }

    public function test_admin_can_topup_user(): void
    {
        $admin = User::factory()->admin()->create();
        $adminToken = $admin->createToken('test')->plainTextToken;
        $user = User::factory()->create(['balance' => '50.0000']);

        $response = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->postJson('/api/v1/balance/topup', [
                'user_id' => $user->id,
                'amount' => '100.00',
                'notes' => 'API topup test',
            ]);

        $response->assertOk()
            ->assertJsonPath('new_balance', '150.0000');

        $this->assertEquals('150.0000', $user->fresh()->balance);
    }

    public function test_client_cannot_topup(): void
    {
        $client = User::factory()->client()->create();
        $token = $client->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/balance/topup', [
                'user_id' => $client->id,
                'amount' => '100.00',
            ]);

        $response->assertForbidden();
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\SipAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SipAccountApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    public function test_admin_can_list_all_sip_accounts(): void
    {
        SipAccount::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/sip-accounts');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_client_can_only_see_own_sip_accounts(): void
    {
        $client = User::factory()->client()->create();
        $clientToken = $client->createToken('test')->plainTextToken;

        SipAccount::factory()->create(['user_id' => $client->id]);
        SipAccount::factory()->count(2)->create(); // other users

        $response = $this->withHeader('Authorization', "Bearer {$clientToken}")
            ->getJson('/api/v1/sip-accounts');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_create_sip_account(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sip-accounts', [
                'user_id' => $user->id,
                'username' => '5001',
                'password' => 'SecurePassword123!',
                'auth_type' => 'password',
                'caller_id_name' => 'Test User',
                'caller_id_number' => '15551234567',
                'max_channels' => 2,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.username', '5001');
    }

    public function test_admin_can_show_sip_account(): void
    {
        $sip = SipAccount::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/sip-accounts/{$sip->id}");

        $response->assertOk()
            ->assertJsonPath('data.username', $sip->username);
    }
}

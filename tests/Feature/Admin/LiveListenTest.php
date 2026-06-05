<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LiveListenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'admin']);
        config()->set('services.listen.token_secret', 'test-secret');
    }

    public function test_super_admin_gets_token_and_audit_row(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)->postJson(
            route('admin.active-calls.listen-token'),
            ['linked_id' => '100.1', 'unique_id' => '100.1', 'caller' => '01711', 'callee' => '8801999'],
        );

        $response->assertOk()->assertJsonStructure(['token']);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'call.listen.start',
        ]);
    }

    public function test_regular_admin_is_forbidden(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)->postJson(
            route('admin.active-calls.listen-token'),
            ['linked_id' => '100.1'],
        )->assertForbidden();
    }

    public function test_requires_linked_id(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->postJson(
            route('admin.active-calls.listen-token'),
            [],
        )->assertStatus(422);
    }
}

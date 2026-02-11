<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_impersonate_reseller(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $reseller = User::factory()->create(['role' => 'reseller', 'status' => 'active']);

        $this->actingAs($superAdmin);

        $response = $this->post(route('admin.impersonate.start', $reseller));

        $response->assertRedirect(route('reseller.dashboard'));
        $this->assertAuthenticatedAs($reseller);
        $this->assertEquals($superAdmin->id, session('impersonator_id'));
    }

    public function test_super_admin_can_impersonate_client(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $client = User::factory()->create(['role' => 'client', 'status' => 'active']);

        $this->actingAs($superAdmin);

        $response = $this->post(route('admin.impersonate.start', $client));

        $response->assertRedirect(route('client.dashboard'));
        $this->assertAuthenticatedAs($client);
    }

    public function test_super_admin_can_impersonate_admin(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($superAdmin);

        $response = $this->post(route('admin.impersonate.start', $admin));

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_super_admin_can_impersonate_recharge_admin(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $rechargeAdmin = User::factory()->create(['role' => 'recharge_admin', 'status' => 'active']);

        $this->actingAs($superAdmin);

        $response = $this->post(route('admin.impersonate.start', $rechargeAdmin));

        $response->assertRedirect(route('recharge-admin.dashboard'));
        $this->assertAuthenticatedAs($rechargeAdmin);
    }

    public function test_super_admin_cannot_impersonate_another_super_admin(): void
    {
        $superAdmin1 = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $superAdmin2 = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);

        $this->actingAs($superAdmin1);

        $response = $this->post(route('admin.impersonate.start', $superAdmin2));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertAuthenticatedAs($superAdmin1);
    }

    public function test_regular_admin_cannot_impersonate(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $reseller = User::factory()->create(['role' => 'reseller', 'status' => 'active']);

        $this->actingAs($admin);

        $response = $this->post(route('admin.impersonate.start', $reseller));

        $response->assertForbidden();
    }

    public function test_can_stop_impersonation(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $reseller = User::factory()->create(['role' => 'reseller', 'status' => 'active']);

        $this->actingAs($superAdmin);

        // Start impersonation
        $this->post(route('admin.impersonate.start', $reseller));
        $this->assertAuthenticatedAs($reseller);

        // Stop impersonation
        $response = $this->post(route('admin.impersonate.stop'));

        $response->assertRedirect(route('admin.users.show', $reseller));
        $this->assertAuthenticatedAs($superAdmin);
        $this->assertNull(session('impersonator_id'));
    }

    public function test_impersonation_is_audited(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $reseller = User::factory()->create(['role' => 'reseller', 'status' => 'active']);

        $this->actingAs($superAdmin);

        $this->post(route('admin.impersonate.start', $reseller));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.impersonation.start',
            'auditable_type' => User::class,
            'auditable_id' => $reseller->id,
        ]);
    }
}

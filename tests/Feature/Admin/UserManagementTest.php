<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $reseller;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'reseller']);
        Role::create(['name' => 'client']);

        // Create admin
        $this->admin = User::factory()->admin()->create();
        $this->admin->assignRole('admin');

        // Create reseller and assign to admin
        $this->reseller = User::factory()->reseller()->create();
        $this->reseller->assignRole('reseller');
        $this->admin->assignedResellers()->attach($this->reseller->id);
    }

    public function test_admin_can_view_users_index(): void
    {
        User::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.index'));

        $response->assertOk();
    }

    public function test_admin_can_create_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'role' => 'client',
                'parent_id' => $this->reseller->id,
                'status' => 'active',
                'billing_type' => 'prepaid',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    public function test_admin_can_toggle_user_status(): void
    {
        // Create a client under the assigned reseller
        $client = User::factory()->client()->create([
            'parent_id' => $this->reseller->id,
            'status' => 'active',
        ]);
        $client->assignRole('client');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.toggle-status', $client));

        $response->assertRedirect();
        $this->assertEquals('suspended', $client->fresh()->status);
    }

    public function test_admin_cannot_toggle_unassigned_user_status(): void
    {
        // Create another reseller NOT assigned to admin
        $otherReseller = User::factory()->reseller()->create();
        $otherReseller->assignRole('reseller');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.toggle-status', $otherReseller));

        $response->assertForbidden();
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $client = User::factory()->client()->create();
        $client->assignRole('client');

        $response = $this->actingAs($client)
            ->get(route('admin.users.index'));

        $response->assertForbidden();
    }
}

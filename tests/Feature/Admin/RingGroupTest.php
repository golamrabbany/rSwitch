<?php

namespace Tests\Feature\Admin;

use App\Models\RingGroup;
use App\Models\SipAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RingGroupTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'reseller']);
        Role::create(['name' => 'client']);

        $this->admin = User::factory()->admin()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_view_ring_groups_index(): void
    {
        RingGroup::factory()->count(3)->create(['user_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.ring-groups.index'));

        $response->assertOk();
    }

    public function test_admin_can_create_ring_group(): void
    {
        $sip1 = SipAccount::factory()->create(['user_id' => $this->admin->id]);
        $sip2 = SipAccount::factory()->create(['user_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.ring-groups.store'), [
                'name' => 'Sales Team',
                'description' => 'Sales ring group',
                'strategy' => 'simultaneous',
                'ring_timeout' => 30,
                'user_id' => $this->admin->id,
                'status' => 'active',
                'members' => [
                    ['sip_account_id' => $sip1->id, 'priority' => 1, 'delay' => 0],
                    ['sip_account_id' => $sip2->id, 'priority' => 2, 'delay' => 5],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ring_groups', ['name' => 'Sales Team']);
        $this->assertEquals(2, RingGroup::first()->members()->count());
    }

    public function test_admin_can_view_ring_group(): void
    {
        $rg = RingGroup::factory()->create(['user_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.ring-groups.show', $rg));

        $response->assertOk();
    }

    public function test_admin_can_update_ring_group(): void
    {
        $rg = RingGroup::factory()->create([
            'user_id' => $this->admin->id,
            'name' => 'Old Name',
        ]);

        $sip = SipAccount::factory()->create(['user_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.ring-groups.update', $rg), [
                'name' => 'New Name',
                'description' => 'Updated',
                'strategy' => 'sequential',
                'ring_timeout' => 45,
                'user_id' => $this->admin->id,
                'status' => 'active',
                'members' => [
                    ['sip_account_id' => $sip->id, 'priority' => 1, 'delay' => 0],
                ],
            ]);

        $response->assertRedirect();
        $this->assertEquals('New Name', $rg->fresh()->name);
        $this->assertEquals('sequential', $rg->fresh()->strategy);
    }

    public function test_admin_can_delete_ring_group(): void
    {
        $rg = RingGroup::factory()->create(['user_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.ring-groups.destroy', $rg));

        $response->assertRedirect();
        $this->assertDatabaseMissing('ring_groups', ['id' => $rg->id]);
    }

    public function test_build_dial_string_simultaneous(): void
    {
        $rg = RingGroup::factory()->create([
            'user_id' => $this->admin->id,
            'strategy' => 'simultaneous',
        ]);

        $sip1 = SipAccount::factory()->create([
            'user_id' => $this->admin->id,
            'username' => '1001',
            'status' => 'active',
        ]);
        $sip2 = SipAccount::factory()->create([
            'user_id' => $this->admin->id,
            'username' => '1002',
            'status' => 'active',
        ]);

        $rg->members()->attach([
            $sip1->id => ['priority' => 1, 'delay' => 0],
            $sip2->id => ['priority' => 2, 'delay' => 0],
        ]);

        $dialString = $rg->buildDialString();

        $this->assertStringContainsString('PJSIP/1001', $dialString);
        $this->assertStringContainsString('PJSIP/1002', $dialString);
        $this->assertStringContainsString('&', $dialString);
    }

    public function test_build_dial_string_empty_members(): void
    {
        $rg = RingGroup::factory()->create([
            'user_id' => $this->admin->id,
            'strategy' => 'simultaneous',
        ]);

        $this->assertEquals('', $rg->buildDialString());
    }

    public function test_non_admin_cannot_access_ring_groups(): void
    {
        $client = User::factory()->client()->create();
        $client->assignRole('client');

        $response = $this->actingAs($client)
            ->get(route('admin.ring-groups.index'));

        $response->assertForbidden();
    }
}

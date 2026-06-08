<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoRechargeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_enable_auto_recharge_on_create(): void
    {
        $super = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);

        $this->actingAs($super)->post(route('admin.users.store'), [
            'name' => 'Test Client', 'username' => '01700000001', 'password' => 'secret12',
            'password_confirmation' => 'secret12', 'role' => 'client', 'billing_type' => 'prepaid',
            'auto_recharge_enabled' => '1',
        ]);

        $this->assertDatabaseHas('users', ['username' => '01700000001', 'auto_recharge_enabled' => 1]);
    }

    public function test_non_super_admin_cannot_set_auto_recharge(): void
    {
        $reseller = User::factory()->create(['role' => 'reseller', 'status' => 'active']);

        $this->actingAs($reseller)->post(route('admin.users.store'), [
            'name' => 'R Client', 'username' => '01700000002', 'password' => 'secret12',
            'password_confirmation' => 'secret12', 'role' => 'client', 'billing_type' => 'prepaid',
            'parent_id' => $reseller->id, 'auto_recharge_enabled' => '1',
        ]);

        $created = User::where('username', '01700000002')->first();
        if ($created) {
            $this->assertFalse((bool) $created->auto_recharge_enabled);
        }
    }
}

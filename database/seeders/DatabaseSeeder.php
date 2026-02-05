<?php

namespace Database\Seeders;

use App\Models\RateGroup;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call(RolePermissionSeeder::class);

        // Create admin user
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@rswitch.local',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'kyc_status' => 'approved',
            'billing_type' => 'postpaid',
        ]);
        $admin->assignRole('admin');

        // Create default rate group
        $rateGroup = RateGroup::create([
            'name' => 'Default',
            'description' => 'Default rate group',
            'type' => 'admin',
            'created_by' => $admin->id,
        ]);

        // Create demo reseller
        $reseller = User::create([
            'name' => 'Demo Reseller',
            'email' => 'reseller@rswitch.local',
            'password' => Hash::make('password'),
            'role' => 'reseller',
            'parent_id' => $admin->id,
            'status' => 'active',
            'kyc_status' => 'approved',
            'billing_type' => 'prepaid',
            'balance' => 1000.0000,
            'rate_group_id' => $rateGroup->id,
        ]);
        $reseller->assignRole('reseller');

        // Create demo client under reseller
        $client = User::create([
            'name' => 'Demo Client',
            'email' => 'client@rswitch.local',
            'password' => Hash::make('password'),
            'role' => 'client',
            'parent_id' => $reseller->id,
            'status' => 'active',
            'kyc_status' => 'approved',
            'billing_type' => 'prepaid',
            'balance' => 100.0000,
            'rate_group_id' => $rateGroup->id,
        ]);
        $client->assignRole('client');

        // Seed system settings
        SystemSetting::seedDefaults();

        // Create sample webhook endpoint for admin
        WebhookEndpoint::create([
            'user_id' => $admin->id,
            'url' => 'https://example.com/webhooks/rswitch',
            'secret' => Str::random(32),
            'events' => ['call.completed', 'call.failed', 'payment.received', 'balance.low'],
            'active' => true,
            'description' => 'Demo webhook endpoint',
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\RateGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
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

        // Create demo client under reseller
        User::create([
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
    }
}

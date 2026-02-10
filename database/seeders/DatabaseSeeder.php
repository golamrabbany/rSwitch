<?php

namespace Database\Seeders;

use App\Models\CallRecord;
use App\Models\Did;
use App\Models\Invoice;
use App\Models\KycDocument;
use App\Models\KycProfile;
use App\Models\Rate;
use App\Models\RateGroup;
use App\Models\SipAccount;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\Trunk;
use App\Models\TrunkRoute;
use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call(RolePermissionSeeder::class);

        // Create super admin user (full system access)
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@rswitch.local',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'status' => 'active',
            'kyc_status' => 'approved',
            'billing_type' => 'postpaid',
        ]);
        $superAdmin->assignRole('super_admin');

        // Create regular admin user (for demo - limited to assigned resellers)
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

        // Create recharge admin
        $rechargeAdmin = User::create([
            'name' => 'Recharge Admin',
            'email' => 'recharge@rswitch.local',
            'password' => Hash::make('password'),
            'role' => 'recharge_admin',
            'status' => 'active',
            'kyc_status' => 'approved',
            'billing_type' => 'postpaid',
        ]);
        $rechargeAdmin->assignRole('recharge_admin');

        // Create default rate group
        $rateGroup = RateGroup::create([
            'name' => 'Default',
            'description' => 'Default rate group',
            'type' => 'admin',
            'created_by' => $superAdmin->id,
        ]);

        // ==========================================
        // DEMO DATA: 10 Resellers
        // ==========================================
        $resellerNames = [
            ['name' => 'Global Telecom Solutions', 'email' => 'reseller1@rswitch.local', 'balance' => 5000],
            ['name' => 'Voice Connect Ltd', 'email' => 'reseller2@rswitch.local', 'balance' => 3500],
            ['name' => 'TeleCom Partners', 'email' => 'reseller3@rswitch.local', 'balance' => 2800],
            ['name' => 'Quick Dial Services', 'email' => 'reseller4@rswitch.local', 'balance' => 4200],
            ['name' => 'Star Communications', 'email' => 'reseller5@rswitch.local', 'balance' => 1500],
            ['name' => 'Prime Voice Networks', 'email' => 'reseller6@rswitch.local', 'balance' => 6000],
            ['name' => 'Connect Plus Inc', 'email' => 'reseller7@rswitch.local', 'balance' => 2200],
            ['name' => 'Swift Telecom', 'email' => 'reseller8@rswitch.local', 'balance' => 3800],
            ['name' => 'Digital Voice Co', 'email' => 'reseller9@rswitch.local', 'balance' => 4500],
            ['name' => 'Metro Dial Services', 'email' => 'reseller10@rswitch.local', 'balance' => 2900],
        ];

        $resellers = [];
        foreach ($resellerNames as $index => $data) {
            $reseller = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'role' => 'reseller',
                'status' => 'active',
                'kyc_status' => 'approved',
                'billing_type' => 'prepaid',
                'balance' => $data['balance'],
                'rate_group_id' => $rateGroup->id,
                'max_channels' => rand(20, 50),
            ]);
            $reseller->assignRole('reseller');
            $resellers[] = $reseller;

            // Assign first 5 resellers to regular admin, next 5 to recharge admin
            if ($index < 5) {
                $admin->assignedResellers()->attach($reseller->id);
            } else {
                $rechargeAdmin->assignedResellers()->attach($reseller->id);
            }
        }

        // ==========================================
        // DEMO DATA: 30 Clients (distributed among resellers)
        // ==========================================
        $clientNames = [
            'Acme Corporation', 'Tech Innovators', 'Business Solutions LLC', 'Global Enterprises',
            'Smart Office Inc', 'Digital Dynamics', 'Cloud Nine Systems', 'Alpha Communications',
            'Beta Services', 'Gamma Tech', 'Delta Solutions', 'Epsilon Group',
            'Zeta Consulting', 'Eta Ventures', 'Theta Corp', 'Iota Industries',
            'Kappa Networks', 'Lambda Labs', 'Mu Media', 'Nu Technologies',
            'Xi Enterprises', 'Omicron Office', 'Pi Partners', 'Rho Resources',
            'Sigma Systems', 'Tau Trading', 'Upsilon Unified', 'Phi Finance',
            'Chi Consulting', 'Psi Productions',
        ];

        $clients = [];
        foreach ($clientNames as $index => $name) {
            // Distribute clients among resellers (3 clients per reseller)
            $parentReseller = $resellers[$index % count($resellers)];

            $client = User::create([
                'name' => $name,
                'email' => 'client' . ($index + 1) . '@rswitch.local',
                'password' => Hash::make('password'),
                'role' => 'client',
                'parent_id' => $parentReseller->id,
                'status' => 'active',
                'kyc_status' => $index < 25 ? 'approved' : 'pending', // Last 5 have pending KYC
                'billing_type' => $index % 3 === 0 ? 'postpaid' : 'prepaid',
                'balance' => rand(50, 500),
                'credit_limit' => $index % 3 === 0 ? rand(100, 500) : 0,
                'rate_group_id' => $rateGroup->id,
                'max_channels' => rand(5, 20),
            ]);
            $client->assignRole('client');
            $clients[] = $client;
        }

        // ==========================================
        // DEMO DATA: KYC Profiles for pending clients
        // ==========================================
        $pendingClients = array_slice($clients, 25, 5);
        $countries = ['US', 'UK', 'DE', 'FR', 'AU'];

        foreach ($pendingClients as $index => $client) {
            KycProfile::create([
                'user_id' => $client->id,
                'account_type' => $index % 2 === 0 ? 'company' : 'individual',
                'full_name' => $client->name,
                'contact_person' => $index % 2 === 0 ? 'John Contact' : null,
                'phone' => '+1-555-' . rand(100, 999) . '-' . rand(1000, 9999),
                'address_line1' => rand(100, 999) . ' Business Ave',
                'city' => 'Metro City',
                'state' => 'State',
                'postal_code' => rand(10000, 99999),
                'country' => $countries[$index],
                'id_type' => 'business_license',
                'id_number' => 'BRN-' . rand(100000, 999999),
                'id_expiry_date' => now()->addYears(rand(2, 5)),
                'submitted_at' => now()->subHours(rand(1, 72)),
            ]);
        }

        // Seed system settings
        SystemSetting::seedDefaults();

        // Create sample webhook endpoint for super admin
        WebhookEndpoint::create([
            'user_id' => $superAdmin->id,
            'url' => 'https://example.com/webhooks/rswitch',
            'secret' => Str::random(32),
            'events' => ['call.completed', 'call.failed', 'payment.received', 'balance.low'],
            'active' => true,
            'description' => 'Demo webhook endpoint',
        ]);

        // ==========================================
        // DEMO DATA: Rates
        // ==========================================
        $rates = [
            ['prefix' => '1', 'destination' => 'USA/Canada', 'rate_per_minute' => '0.0100'],
            ['prefix' => '1212', 'destination' => 'USA - New York', 'rate_per_minute' => '0.0080'],
            ['prefix' => '1310', 'destination' => 'USA - Los Angeles', 'rate_per_minute' => '0.0080'],
            ['prefix' => '1415', 'destination' => 'USA - San Francisco', 'rate_per_minute' => '0.0085'],
            ['prefix' => '1305', 'destination' => 'USA - Miami', 'rate_per_minute' => '0.0090'],
            ['prefix' => '44', 'destination' => 'United Kingdom', 'rate_per_minute' => '0.0150'],
            ['prefix' => '4420', 'destination' => 'UK - London', 'rate_per_minute' => '0.0120'],
            ['prefix' => '49', 'destination' => 'Germany', 'rate_per_minute' => '0.0180'],
            ['prefix' => '33', 'destination' => 'France', 'rate_per_minute' => '0.0170'],
            ['prefix' => '34', 'destination' => 'Spain', 'rate_per_minute' => '0.0160'],
            ['prefix' => '39', 'destination' => 'Italy', 'rate_per_minute' => '0.0175'],
            ['prefix' => '61', 'destination' => 'Australia', 'rate_per_minute' => '0.0200'],
            ['prefix' => '81', 'destination' => 'Japan', 'rate_per_minute' => '0.0250'],
            ['prefix' => '86', 'destination' => 'China', 'rate_per_minute' => '0.0150'],
            ['prefix' => '91', 'destination' => 'India', 'rate_per_minute' => '0.0120'],
            ['prefix' => '92', 'destination' => 'Pakistan', 'rate_per_minute' => '0.0180'],
            ['prefix' => '880', 'destination' => 'Bangladesh', 'rate_per_minute' => '0.0200'],
            ['prefix' => '971', 'destination' => 'UAE', 'rate_per_minute' => '0.0300'],
            ['prefix' => '966', 'destination' => 'Saudi Arabia', 'rate_per_minute' => '0.0280'],
            ['prefix' => '65', 'destination' => 'Singapore', 'rate_per_minute' => '0.0220'],
        ];

        foreach ($rates as $rate) {
            Rate::create([
                'rate_group_id' => $rateGroup->id,
                'prefix' => $rate['prefix'],
                'destination' => $rate['destination'],
                'rate_per_minute' => $rate['rate_per_minute'],
                'connection_fee' => '0.0000',
                'min_duration' => 1,
                'billing_increment' => 6,
                'effective_date' => now()->subMonth(),
                'status' => 'active',
            ]);
        }

        // ==========================================
        // DEMO DATA: Trunks
        // ==========================================
        $outboundTrunk = Trunk::create([
            'name' => 'Primary Outbound',
            'provider' => 'Carrier One',
            'host' => 'sip.carrier1.com',
            'port' => 5060,
            'transport' => 'udp',
            'direction' => 'outgoing',
            'max_channels' => 100,
            'status' => 'active',
            'cli_mode' => 'passthrough',
            'codec_allow' => 'ulaw,alaw,g729',
        ]);

        $outboundTrunk2 = Trunk::create([
            'name' => 'Secondary Outbound',
            'provider' => 'Carrier Three',
            'host' => 'sip.carrier3.com',
            'port' => 5060,
            'transport' => 'udp',
            'direction' => 'outgoing',
            'max_channels' => 50,
            'status' => 'active',
            'cli_mode' => 'passthrough',
            'codec_allow' => 'ulaw,alaw',
        ]);

        $inboundTrunk = Trunk::create([
            'name' => 'Primary Inbound',
            'provider' => 'Carrier Two',
            'host' => 'sip.carrier2.com',
            'port' => 5060,
            'transport' => 'udp',
            'direction' => 'incoming',
            'incoming_auth_type' => 'ip',
            'max_channels' => 50,
            'status' => 'active',
            'codec_allow' => 'ulaw,alaw,g729',
        ]);

        // ==========================================
        // DEMO DATA: Trunk Routes
        // ==========================================
        TrunkRoute::create([
            'trunk_id' => $outboundTrunk->id,
            'prefix' => '1',
            'priority' => 1,
            'weight' => 100,
            'status' => 'active',
        ]);

        TrunkRoute::create([
            'trunk_id' => $outboundTrunk2->id,
            'prefix' => '1',
            'priority' => 2,
            'weight' => 50,
            'status' => 'active',
        ]);

        TrunkRoute::create([
            'trunk_id' => $outboundTrunk->id,
            'prefix' => '44',
            'priority' => 1,
            'weight' => 100,
            'status' => 'active',
        ]);

        TrunkRoute::create([
            'trunk_id' => $outboundTrunk->id,
            'prefix' => '49',
            'priority' => 1,
            'weight' => 100,
            'status' => 'active',
        ]);

        // ==========================================
        // DEMO DATA: 100 SIP Accounts
        // ==========================================
        $sipAccounts = [];
        $sipCounter = 100000;
        $allUsers = array_merge($resellers, $clients);

        // Create ~2-3 SIP accounts per user to reach 100
        foreach ($allUsers as $user) {
            $numSipAccounts = $user->role === 'reseller' ? rand(3, 5) : rand(2, 3);

            for ($i = 0; $i < $numSipAccounts; $i++) {
                $sipCounter++;

                $authType = ['password', 'password', 'password', 'ip', 'both'][array_rand(['password', 'password', 'password', 'ip', 'both'])];

                $sip = SipAccount::create([
                    'user_id' => $user->id,
                    'username' => (string) $sipCounter,
                    'password' => Str::random(16), // Always generate password
                    'auth_type' => $authType,
                    'allowed_ips' => $authType !== 'password' ? '192.168.' . rand(1, 254) . '.' . rand(1, 254) : null,
                    'caller_id_number' => '1555' . rand(1000000, 9999999),
                    'caller_id_name' => $user->name . ' Line ' . ($i + 1),
                    'max_channels' => rand(2, 10),
                    'codec_allow' => ['ulaw,alaw,g729', 'ulaw,alaw', 'g729,ulaw'][array_rand(['ulaw,alaw,g729', 'ulaw,alaw', 'g729,ulaw'])],
                    'status' => rand(1, 10) > 1 ? 'active' : 'suspended', // 90% active
                ]);

                $sipAccounts[] = $sip;

                // Stop after 100 SIP accounts
                if (count($sipAccounts) >= 100) {
                    break 2;
                }
            }
        }

        // ==========================================
        // DEMO DATA: DIDs
        // ==========================================
        $didNumbers = [
            '18005551001', '18005551002', '18005551003', '18005551004', '18005551005',
            '18005552001', '18005552002', '18005552003', '18005552004', '18005552005',
            '18005553001', '18005553002', '18005553003', '18005553004', '18005553005',
            '18005554001', '18005554002', '18005554003', '18005554004', '18005554005',
        ];

        $didIndex = 0;
        foreach ($didNumbers as $number) {
            $assignedUser = $didIndex < 15 ? $allUsers[$didIndex % count($allUsers)] : null;
            $destinationSip = $assignedUser ? $sipAccounts[$didIndex % count($sipAccounts)] : null;

            Did::create([
                'number' => $number,
                'provider' => 'Carrier Two',
                'trunk_id' => $inboundTrunk->id,
                'assigned_to_user_id' => $assignedUser?->id,
                'destination_type' => $assignedUser ? 'sip_account' : 'external',
                'destination_id' => $destinationSip?->id,
                'destination_number' => $assignedUser ? null : '+1555' . rand(1000000, 9999999),
                'monthly_cost' => rand(100, 200) / 100,
                'monthly_price' => rand(200, 400) / 100,
                'status' => $assignedUser ? 'active' : 'unassigned',
            ]);
            $didIndex++;
        }

        // ==========================================
        // DEMO DATA: Call Records (last 14 days)
        // ==========================================
        $dispositions = ['ANSWERED', 'ANSWERED', 'ANSWERED', 'ANSWERED', 'NO ANSWER', 'BUSY', 'FAILED'];
        $prefixes = ['1212', '1310', '1415', '44', '4420', '49', '91', '880', '86', '65'];
        $rateModels = Rate::all()->keyBy('prefix');

        for ($i = 0; $i < 500; $i++) {
            $callStart = now()->subDays(rand(0, 13))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
            $disposition = $dispositions[array_rand($dispositions)];
            $duration = $disposition === 'ANSWERED' ? rand(30, 900) : 0;
            $billsec = $disposition === 'ANSWERED' ? max(0, $duration - rand(5, 15)) : 0;
            $prefix = $prefixes[array_rand($prefixes)];
            $calledNumber = $prefix . rand(1000000, 9999999);

            $randomUser = $allUsers[array_rand($allUsers)];
            $userSipAccounts = array_filter($sipAccounts, fn($s) => $s->user_id === $randomUser->id);
            $randomSip = !empty($userSipAccounts) ? $userSipAccounts[array_rand($userSipAccounts)] : $sipAccounts[0];

            $rate = $rateModels->get($prefix);
            $cost = $billsec > 0 && $rate ? bcmul(bcdiv((string) $billsec, '60', 6), $rate->rate_per_minute, 4) : '0.0000';

            CallRecord::create([
                'uuid' => Str::uuid(),
                'user_id' => $randomUser->id,
                'sip_account_id' => $randomSip->id,
                'outgoing_trunk_id' => rand(0, 1) ? $outboundTrunk->id : $outboundTrunk2->id,
                'caller' => '1555' . rand(1000000, 9999999),
                'caller_id' => $randomUser->name,
                'callee' => $calledNumber,
                'destination' => $rate ? $rate->destination : 'Unknown',
                'matched_prefix' => $prefix,
                'rate_per_minute' => $rate ? $rate->rate_per_minute : '0.0000',
                'call_start' => $callStart,
                'call_end' => $callStart->copy()->addSeconds($duration),
                'duration' => $duration,
                'billsec' => $billsec,
                'billable_duration' => $billsec,
                'disposition' => $disposition,
                'hangup_cause' => $disposition === 'ANSWERED' ? 'NORMAL_CLEARING' : 'NO_USER_RESPONSE',
                'call_flow' => 'sip_to_trunk',
                'status' => $billsec > 0 ? 'rated' : 'unbillable',
                'rated_at' => $billsec > 0 ? now() : null,
                'total_cost' => $cost,
                'reseller_cost' => bcmul($cost, '0.80', 4),
            ]);
        }

        // ==========================================
        // DEMO DATA: Transactions
        // ==========================================
        foreach ($resellers as $reseller) {
            // Initial top-up
            Transaction::create([
                'user_id' => $reseller->id,
                'type' => 'topup',
                'amount' => rand(500, 2000) . '.0000',
                'balance_after' => $reseller->balance,
                'description' => 'Initial top-up',
                'source' => 'bank_transfer',
                'created_by' => $superAdmin->id,
                'created_at' => now()->subDays(rand(20, 60)),
            ]);
        }

        foreach (array_slice($clients, 0, 15) as $client) {
            Transaction::create([
                'user_id' => $client->id,
                'type' => 'topup',
                'amount' => rand(50, 300) . '.0000',
                'balance_after' => $client->balance,
                'description' => 'Balance top-up',
                'source' => 'manual_reseller',
                'created_by' => $client->parent_id,
                'created_at' => now()->subDays(rand(5, 30)),
            ]);
        }

        // ==========================================
        // DEMO DATA: Invoices
        // ==========================================
        foreach (array_slice($resellers, 0, 5) as $index => $reseller) {
            Invoice::create([
                'invoice_number' => 'INV-' . now()->subMonth()->format('Ymd') . '-' . str_pad($index + 1, 5, '0', STR_PAD_LEFT),
                'user_id' => $reseller->id,
                'period_start' => now()->subMonth()->startOfMonth(),
                'period_end' => now()->subMonth()->endOfMonth(),
                'call_charges' => rand(50, 500) . '.' . rand(10, 99),
                'did_charges' => rand(5, 30) . '.00',
                'tax_amount' => '0.0000',
                'total_amount' => rand(60, 530) . '.' . rand(10, 99),
                'status' => ['paid', 'paid', 'issued', 'draft'][array_rand(['paid', 'paid', 'issued', 'draft'])],
                'due_date' => now()->subDays(rand(-15, 15)),
                'paid_at' => rand(0, 1) ? now()->subDays(rand(1, 10)) : null,
            ]);
        }

        // ==========================================
        // Populate CDR Summary (for dashboard stats)
        // ==========================================
        DB::statement("
            INSERT INTO cdr_summary_daily (user_id, date, total_calls, answered_calls, total_duration, total_billable, total_cost, total_reseller_cost, updated_at)
            SELECT
                user_id,
                DATE(call_start) as date,
                COUNT(*) as total_calls,
                SUM(CASE WHEN disposition = 'ANSWERED' THEN 1 ELSE 0 END) as answered_calls,
                SUM(duration) as total_duration,
                SUM(billable_duration) as total_billable,
                SUM(total_cost) as total_cost,
                SUM(reseller_cost) as total_reseller_cost,
                NOW() as updated_at
            FROM call_records
            GROUP BY user_id, DATE(call_start)
            ON DUPLICATE KEY UPDATE
                total_calls = VALUES(total_calls),
                answered_calls = VALUES(answered_calls),
                total_duration = VALUES(total_duration),
                total_billable = VALUES(total_billable),
                total_cost = VALUES(total_cost),
                total_reseller_cost = VALUES(total_reseller_cost),
                updated_at = NOW()
        ");

        $this->command->info('Seeded: ' . User::where('role', 'reseller')->count() . ' resellers');
        $this->command->info('Seeded: ' . User::where('role', 'client')->count() . ' clients');
        $this->command->info('Seeded: ' . SipAccount::count() . ' SIP accounts');
        $this->command->info('Seeded: ' . CallRecord::count() . ' call records');
    }
}

<?php

namespace Database\Seeders;

use App\Models\CallRecord;
use App\Models\Did;
use App\Models\Invoice;
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

        // ==========================================
        // DEMO DATA: Rates
        // ==========================================
        $rates = [
            ['prefix' => '1', 'destination' => 'USA/Canada', 'rate_per_minute' => '0.0100'],
            ['prefix' => '1212', 'destination' => 'USA - New York', 'rate_per_minute' => '0.0080'],
            ['prefix' => '1310', 'destination' => 'USA - Los Angeles', 'rate_per_minute' => '0.0080'],
            ['prefix' => '44', 'destination' => 'United Kingdom', 'rate_per_minute' => '0.0150'],
            ['prefix' => '49', 'destination' => 'Germany', 'rate_per_minute' => '0.0180'],
            ['prefix' => '33', 'destination' => 'France', 'rate_per_minute' => '0.0170'],
            ['prefix' => '61', 'destination' => 'Australia', 'rate_per_minute' => '0.0200'],
            ['prefix' => '81', 'destination' => 'Japan', 'rate_per_minute' => '0.0250'],
            ['prefix' => '86', 'destination' => 'China', 'rate_per_minute' => '0.0150'],
            ['prefix' => '91', 'destination' => 'India', 'rate_per_minute' => '0.0120'],
            ['prefix' => '880', 'destination' => 'Bangladesh', 'rate_per_minute' => '0.0200'],
            ['prefix' => '971', 'destination' => 'UAE', 'rate_per_minute' => '0.0300'],
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
            'trunk_id' => $outboundTrunk->id,
            'prefix' => '44',
            'priority' => 1,
            'weight' => 100,
            'status' => 'active',
        ]);

        // ==========================================
        // DEMO DATA: SIP Accounts
        // ==========================================
        $sipReseller = SipAccount::create([
            'user_id' => $reseller->id,
            'username' => '100001',
            'password' => Str::random(16),
            'auth_type' => 'password',
            'caller_id_number' => '15551000001',
            'caller_id_name' => 'Demo Reseller',
            'max_channels' => 10,
            'codec_allow' => 'ulaw,alaw,g729',
            'status' => 'active',
        ]);

        $sipClient1 = SipAccount::create([
            'user_id' => $client->id,
            'username' => '200001',
            'password' => Str::random(16),
            'auth_type' => 'password',
            'caller_id_number' => '15552000001',
            'caller_id_name' => 'Demo Client',
            'max_channels' => 5,
            'codec_allow' => 'ulaw,alaw,g729',
            'status' => 'active',
        ]);

        $sipClient2 = SipAccount::create([
            'user_id' => $client->id,
            'username' => '200002',
            'password' => Str::random(16),
            'auth_type' => 'password',
            'caller_id_number' => '15552000002',
            'caller_id_name' => 'Demo Client 2',
            'max_channels' => 5,
            'codec_allow' => 'ulaw,alaw',
            'status' => 'active',
        ]);

        // ==========================================
        // DEMO DATA: DIDs
        // ==========================================
        Did::create([
            'number' => '18005551234',
            'provider' => 'Carrier Two',
            'trunk_id' => $inboundTrunk->id,
            'assigned_to_user_id' => $reseller->id,
            'destination_type' => 'sip_account',
            'destination_id' => $sipReseller->id,
            'monthly_cost' => '1.5000',
            'monthly_price' => '3.0000',
            'status' => 'active',
        ]);

        Did::create([
            'number' => '18005555678',
            'provider' => 'Carrier Two',
            'trunk_id' => $inboundTrunk->id,
            'assigned_to_user_id' => $client->id,
            'destination_type' => 'sip_account',
            'destination_id' => $sipClient1->id,
            'monthly_cost' => '1.5000',
            'monthly_price' => '2.5000',
            'status' => 'active',
        ]);

        Did::create([
            'number' => '18005559999',
            'provider' => 'Carrier Two',
            'trunk_id' => $inboundTrunk->id,
            'assigned_to_user_id' => null,
            'destination_type' => 'external',
            'destination_number' => '+15559999999',
            'monthly_cost' => '1.0000',
            'monthly_price' => '2.0000',
            'status' => 'unassigned',
        ]);

        // ==========================================
        // DEMO DATA: Call Records (last 7 days)
        // ==========================================
        $dispositions = ['ANSWERED', 'ANSWERED', 'ANSWERED', 'NO ANSWER', 'BUSY', 'FAILED'];
        $prefixes = ['1212', '1310', '44', '49', '91', '880'];

        for ($i = 0; $i < 50; $i++) {
            $callStart = now()->subDays(rand(0, 6))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
            $disposition = $dispositions[array_rand($dispositions)];
            $duration = $disposition === 'ANSWERED' ? rand(30, 600) : 0;
            $billsec = $disposition === 'ANSWERED' ? max(0, $duration - rand(5, 15)) : 0;
            $prefix = $prefixes[array_rand($prefixes)];
            $calledNumber = $prefix . rand(1000000, 9999999);

            $rate = Rate::where('prefix', $prefix)->first();
            $cost = $billsec > 0 && $rate ? bcmul(bcdiv((string) $billsec, '60', 6), $rate->rate_per_minute, 4) : '0.0000';

            CallRecord::create([
                'uuid' => Str::uuid(),
                'user_id' => $i % 3 === 0 ? $reseller->id : $client->id,
                'sip_account_id' => $i % 3 === 0 ? $sipReseller->id : $sipClient1->id,
                'outgoing_trunk_id' => $outboundTrunk->id,
                'caller' => '1555' . rand(1000000, 9999999),
                'caller_id' => 'Demo Caller',
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
        Transaction::create([
            'user_id' => $reseller->id,
            'type' => 'topup',
            'amount' => '500.0000',
            'balance_after' => '500.0000',
            'description' => 'Initial top-up',
            'created_by' => $admin->id,
            'created_at' => now()->subDays(30),
        ]);

        Transaction::create([
            'user_id' => $reseller->id,
            'type' => 'topup',
            'amount' => '500.0000',
            'balance_after' => '1000.0000',
            'description' => 'Monthly top-up',
            'created_by' => $admin->id,
            'created_at' => now()->subDays(15),
        ]);

        Transaction::create([
            'user_id' => $client->id,
            'type' => 'topup',
            'amount' => '100.0000',
            'balance_after' => '100.0000',
            'description' => 'Initial balance',
            'created_by' => $reseller->id,
            'created_at' => now()->subDays(20),
        ]);

        // ==========================================
        // DEMO DATA: Invoices
        // ==========================================
        Invoice::create([
            'invoice_number' => 'INV-' . now()->subMonth()->format('Ymd') . '-00001',
            'user_id' => $reseller->id,
            'period_start' => now()->subMonth()->startOfMonth(),
            'period_end' => now()->subMonth()->endOfMonth(),
            'call_charges' => '45.2500',
            'did_charges' => '3.0000',
            'tax_amount' => '0.0000',
            'total_amount' => '48.2500',
            'status' => 'paid',
            'due_date' => now()->subDays(15),
            'paid_at' => now()->subDays(10),
        ]);

        Invoice::create([
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-00001',
            'user_id' => $client->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now(),
            'call_charges' => '12.7500',
            'did_charges' => '2.5000',
            'tax_amount' => '0.0000',
            'total_amount' => '15.2500',
            'status' => 'issued',
            'due_date' => now()->addDays(30),
        ]);

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
    }
}

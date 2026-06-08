<?php

namespace App\Console\Commands;

use App\Models\SipAccount;
use App\Models\User;
use App\Services\SipProvisioningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * One-off bulk provisioner: creates client users + 1 SIP account each for a
 * block of Bangladesh-format numbers under a reseller, KYC-approved, prepaid
 * with 0 balance, assigned a rate group, 2 channels each.
 *
 * SIP accounts are provisioned to Asterisk realtime with skipReload, then the
 * forced single codec (ulaw) is widened to ulaw,alaw,g729 (g729 handsets), and
 * a single pjsip reload runs at the very end.
 */
class ProvisionCyberNestClients extends Command
{
    protected $signature = 'cybernest:provision-clients
        {--reseller=46 : Reseller user id to nest clients under}
        {--rate-group=2 : rate_group_id to assign (Client Tariff)}
        {--channels=2 : max_channels for client and SIP}
        {--codec=ulaw,alaw,g729 : ps_endpoints.allow override for the new endpoints}
        {--limit=0 : only process the first N numbers (0 = all)}
        {--dry-run : build the CSV + report counts without writing any records}';

    protected $description = 'Bulk-create CyberNest clients + SIP accounts (BD numbers)';

    /** Inclusive integer ranges (leading 0 prefixed back on) */
    private array $ranges = [
        [9603125000, 9603125999],
        [9603128000, 9603128999],
    ];

    public function handle(SipProvisioningService $prov): int
    {
        $resellerId  = (int) $this->option('reseller');
        $rateGroupId = (int) $this->option('rate-group');
        $channels    = (int) $this->option('channels');
        $codec       = (string) $this->option('codec');
        $limit       = (int) $this->option('limit');
        $dry         = (bool) $this->option('dry-run');

        $reseller = User::find($resellerId);
        if (! $reseller || $reseller->role !== 'reseller') {
            $this->error("Reseller id {$resellerId} not found or not a reseller.");
            return self::FAILURE;
        }

        $numbers = [];
        foreach ($this->ranges as [$start, $end]) {
            for ($n = $start; $n <= $end; $n++) {
                $numbers[] = '0' . $n; // 9603125000 -> 09603125000
            }
        }
        if ($limit > 0) {
            $numbers = array_slice($numbers, 0, $limit);
        }

        $this->info(sprintf(
            '%d numbers | reseller=%s (#%d) | rate_group=%d | channels=%d | codec=%s | %s',
            count($numbers), $reseller->name, $reseller->id, $rateGroupId, $channels, $codec,
            $dry ? 'DRY-RUN' : 'LIVE'
        ));

        $csvPath = storage_path('cybernest_clients_' . date('Ymd_His') . '.csv');
        $csv = fopen($csvPath, 'w');
        fputcsv($csv, ['number', 'client_login_password', 'sip_password']);

        $created = 0; $skipped = 0; $provisioned = [];
        $bar = $this->output->createProgressBar(count($numbers));
        $bar->start();

        foreach (array_chunk($numbers, 200) as $chunk) {
            DB::transaction(function () use ($chunk, $reseller, $rateGroupId, $channels, $prov, $dry, $csv, &$created, &$skipped, &$provisioned, $bar) {
                foreach ($chunk as $num) {
                    if (User::where('username', $num)->exists() || SipAccount::where('username', $num)->exists()) {
                        $skipped++; $bar->advance(); continue;
                    }

                    $clientPw = Str::random(12);
                    $sipPw    = Str::random(16);

                    if ($dry) {
                        fputcsv($csv, [$num, $clientPw, $sipPw]);
                        $created++; $bar->advance(); continue;
                    }

                    $client = User::create([
                        'name'            => "CyberNest {$num}",
                        'username'        => $num,
                        'email'           => null,
                        'password'        => Hash::make($clientPw),
                        'role'            => 'client',
                        'parent_id'       => $reseller->id,
                        'status'          => 'active',
                        'kyc_status'      => 'approved',
                        'kyc_verified_at' => now(),
                        'billing_type'    => 'prepaid',
                        'balance'         => 0,
                        'credit_limit'    => 0,
                        'currency'        => $reseller->currency ?: 'BDT',
                        'rate_group_id'   => $rateGroupId,
                        'max_channels'    => $channels,
                    ]); // hierarchy_path auto-set by HasHierarchy on 'created'

                    $sip = SipAccount::create([
                        'user_id'          => $client->id,
                        'username'         => $num,
                        'password'         => $sipPw,
                        'auth_type'        => 'password',
                        'caller_id_name'   => $num,
                        'caller_id_number' => $num,
                        'max_channels'     => $channels,
                        'codec_allow'      => 'ulaw,alaw,g729',
                        'status'           => 'active',
                    ]);

                    $prov->provision($sip, skipReload: true);
                    $provisioned[] = $num;
                    fputcsv($csv, [$num, $clientPw, $sipPw]);
                    $created++; $bar->advance();
                }
            });
        }

        $bar->finish(); $this->newLine();
        fclose($csv);

        // provision() hardcodes ps_endpoints.allow = DEFAULT_CODEC (ulaw).
        // Widen it so g729-only handsets don't get 488 Not Acceptable Here.
        if (! $dry && $provisioned) {
            foreach (array_chunk($provisioned, 500) as $idChunk) {
                DB::table('ps_endpoints')->whereIn('id', $idChunk)->update(['allow' => $codec]);
            }
            $this->info('Reloading PJSIP (single reload after batch)...');
            $prov->reloadPjsip();
        }

        $this->info("Done. created={$created} skipped={$skipped} csv={$csvPath}");
        return self::SUCCESS;
    }
}

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
        {--ranges= : Comma-separated inclusive number ranges, e.g. 09603519000-09603519999,09603521000-09603521999 (default = original CyberNest blocks)}
        {--limit=0 : only process the first N numbers (0 = all)}
        {--dry-run : build the CSV + report counts without writing any records}';

    protected $description = 'Bulk-create clients + SIP accounts for blocks of BD numbers under a reseller';

    /** Default inclusive ranges, as zero-padded number strings */
    private array $defaultRanges = [
        ['09603125000', '09603125999'],
        ['09603128000', '09603128999'],
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

        try {
            $ranges = $this->parseRanges((string) $this->option('ranges'));
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $numbers = [];
        foreach ($ranges as [$start, $end]) {
            $width = strlen($start);
            for ($n = (int) $start; $n <= (int) $end; $n++) {
                // keep the leading zero(s): 9603125000 -> 09603125000
                $numbers[] = str_pad((string) $n, $width, '0', STR_PAD_LEFT);
            }
        }
        if ($limit > 0) {
            $numbers = array_slice($numbers, 0, $limit);
        }

        $this->info(sprintf(
            '%d numbers | %s | reseller=%s (#%d) | rate_group=%d | channels=%d | codec=%s | %s',
            count($numbers),
            implode(', ', array_map(fn ($r) => "{$r[0]}-{$r[1]}", $ranges)),
            $reseller->name, $reseller->id, $rateGroupId, $channels, $codec,
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
                        'name'            => "{$reseller->name} {$num}",
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

    /**
     * Parse --ranges into a list of [start, end] zero-padded number strings.
     * Empty input falls back to the original CyberNest blocks.
     *
     * @return array<int, array{0: string, 1: string}>
     * @throws \InvalidArgumentException
     */
    private function parseRanges(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return $this->defaultRanges;
        }

        $ranges = [];
        foreach (explode(',', $raw) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $bounds = explode('-', $part);
            if (count($bounds) !== 2) {
                throw new \InvalidArgumentException("Bad range '{$part}': expected START-END.");
            }

            [$start, $end] = array_map('trim', $bounds);
            if (! ctype_digit($start) || ! ctype_digit($end)) {
                throw new \InvalidArgumentException("Bad range '{$part}': both bounds must be digits only.");
            }
            if (strlen($start) !== strlen($end)) {
                throw new \InvalidArgumentException("Bad range '{$part}': bounds must have the same number of digits.");
            }
            if ((int) $start > (int) $end) {
                throw new \InvalidArgumentException("Bad range '{$part}': START is greater than END.");
            }

            $ranges[] = [$start, $end];
        }

        if (! $ranges) {
            throw new \InvalidArgumentException('--ranges was given but contained no usable range.');
        }

        return $ranges;
    }
}

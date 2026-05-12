<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportWeblinkPayments extends Command
{
    protected $signature = 'import:weblink-payments
        {path : Path to rbilling SQL dump}
        {--dry-run : Preview without writing}
        {--force : Skip confirmation prompt}';

    protected $description = 'Update reseller/client balance + insert payment logs from a rbilling SQL dump (idempotent on gateway_transaction_id).';

    private string $tempDb = 'weblink_payments';

    /** dump accounts.id → local users.id */
    private array $accountToUserId = [];

    private array $stats = [
        'users_matched_by_sip' => 0,
        'users_matched_by_email' => 0,
        'balances_updated' => 0,
        'payments_inserted' => 0,
        'payments_skipped_duplicate' => 0,
        'payments_skipped_no_user' => 0,
        'payments_skipped_admin' => 0,
    ];

    public function handle(): int
    {
        $path = $this->argument('path');
        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info('=== WebLink Payments Import ===');
        $this->line("Source: {$path}");
        $this->line('Mode:   ' . ($dryRun ? 'DRY RUN' : 'LIVE'));
        $this->newLine();

        if (!$dryRun && !$this->option('force')) {
            if (!$this->confirm('Proceed?')) {
                return self::FAILURE;
            }
        }

        try {
            $this->phaseA_loadSql($path);
            $this->phaseB_buildMatchMap();
            $this->phaseC_updateBalances($dryRun);
            $this->phaseD_insertPayments($dryRun);

            $this->dropTempDb();
            $this->printSummary();

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed: {$e->getMessage()}");
            $this->line($e->getTraceAsString());
            $this->dropTempDb();
            return self::FAILURE;
        }
    }

    private function phaseA_loadSql(string $path): void
    {
        $this->info('Phase A: Loading SQL into temp database...');

        $connection = config('database.default');
        $host = config("database.connections.{$connection}.host");
        $port = config("database.connections.{$connection}.port");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");

        DB::statement("DROP DATABASE IF EXISTS `{$this->tempDb}`");
        DB::statement("CREATE DATABASE `{$this->tempDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $cmd = sprintf(
            'mysql -h %s -P %s -u %s %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($this->tempDb)
        );
        $env = $password ? ['MYSQL_PWD' => $password, 'PATH' => getenv('PATH') ?: '/usr/bin:/usr/local/bin'] : null;
        $descriptors = [0 => ['file', $path, 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

        $process = proc_open($cmd, $descriptors, $pipes, null, $env);
        if (!is_resource($process)) {
            throw new \RuntimeException('Failed to start mysql process');
        }
        stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        if ($exit !== 0) {
            throw new \RuntimeException("MySQL import failed (exit {$exit}): {$stderr}");
        }
        $this->info('  SQL loaded.');
    }

    private function phaseB_buildMatchMap(): void
    {
        $this->info('Phase B: Building user match map...');

        // Cache local SIP usernames → user_id
        $localSipByUsername = [];
        foreach (DB::table('sip_accounts')->select('username', 'user_id')->get() as $sip) {
            $localSipByUsername[$sip->username] = $sip->user_id;
        }
        $this->line('  Local SIPs cached: ' . count($localSipByUsername));

        // Pass 1: SIP pivot
        $rows = DB::select(
            "SELECT id_client, name FROM `{$this->tempDb}`.`sipusers` WHERE type='friend' AND status = 1"
        );
        foreach ($rows as $row) {
            $username = trim($row->name);
            $clientId = (int) $row->id_client;
            if ($username === '' || $clientId <= 0) continue;
            if (isset($this->accountToUserId[$clientId])) continue;
            if (isset($localSipByUsername[$username])) {
                $this->accountToUserId[$clientId] = $localSipByUsername[$username];
                $this->stats['users_matched_by_sip']++;
            }
        }
        $this->line('  Matched via SIP pivot:    ' . $this->stats['users_matched_by_sip']);

        // Pass 2: email fallback
        $accounts = DB::select(
            "SELECT id, email FROM `{$this->tempDb}`.`accounts`
             WHERE status = 1 AND account_type IN (1,2,3,4,5)
             GROUP BY id"
        );
        foreach ($accounts as $a) {
            $accountId = (int) $a->id;
            if (isset($this->accountToUserId[$accountId])) continue;

            $email = trim((string) $a->email);
            if ($email === '' || str_ends_with(strtolower($email), '@weblink.import')) continue;
            if (preg_match('/\+\d+@/', $email)) continue;
            $email = preg_replace('/_del_[A-Z]$/', '', $email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

            $userId = DB::table('users')->whereRaw('LOWER(email) = ?', [strtolower($email)])->value('id');
            if ($userId) {
                $this->accountToUserId[$accountId] = $userId;
                $this->stats['users_matched_by_email']++;
            }
        }
        $this->line('  Matched via email fallback: ' . $this->stats['users_matched_by_email']);
        $this->line('  Total matched:            ' . count($this->accountToUserId));
    }

    private function phaseC_updateBalances(bool $dryRun): void
    {
        $this->info('Phase C: Updating reseller/client balances...');

        $rows = DB::select(
            "SELECT id, balance, credit FROM `{$this->tempDb}`.`accounts`
             WHERE status = 1 AND account_type IN (1,2,3,4,5)
             GROUP BY id"
        );

        foreach ($rows as $row) {
            $accountId = (int) $row->id;
            if (!isset($this->accountToUserId[$accountId])) continue;

            if (!$dryRun) {
                DB::table('users')
                    ->where('id', $this->accountToUserId[$accountId])
                    ->update([
                        'balance' => $row->balance ?? 0,
                        'credit_limit' => $row->credit ?? 0,
                        'updated_at' => now(),
                    ]);
            }
            $this->stats['balances_updated']++;
        }

        $this->info("  Balances updated: {$this->stats['balances_updated']}");
    }

    private function phaseD_insertPayments(bool $dryRun): void
    {
        $this->info('Phase D: Inserting payment logs (reseller + client only)...');

        // Cache existing payment gateway transaction IDs to skip duplicates
        $existingTxnIds = array_flip(
            DB::table('payments')->whereNotNull('gateway_transaction_id')->pluck('gateway_transaction_id')->all()
        );

        $rows = DB::select(
            "SELECT id, trans_id, mar_trans_id, account_id, account_type, amount, payment_processor,
                    source, bonus, description, user_id, remarks, status, ipaddress, creationdate
             FROM `{$this->tempDb}`.`payments`
             WHERE account_type IN (1, 2)
             ORDER BY id"
        );

        $batch = [];
        $total = count($rows);
        $processed = 0;

        foreach ($rows as $row) {
            $accountId = (int) $row->account_id;
            $userId = $this->accountToUserId[$accountId] ?? null;
            if ($userId === null) {
                $this->stats['payments_skipped_no_user']++;
                continue;
            }

            $txnId = $row->trans_id ?: $row->mar_trans_id;
            if ($txnId && isset($existingTxnIds[$txnId])) {
                $this->stats['payments_skipped_duplicate']++;
                continue;
            }

            $createdAt = $row->creationdate;
            if (!$createdAt || $createdAt === '0000-00-00 00:00:00') {
                $createdAt = now()->toDateTimeString();
            }

            $status = match ((int) $row->status) {
                1 => 'completed',
                -1 => 'failed',
                default => 'pending',
            };

            $batch[] = [
                'user_id' => $userId,
                'amount' => $row->amount ?? 0,
                'currency' => 'BDT',
                'payment_method' => $this->mapPaymentMethod($row->source, $row->payment_processor),
                'gateway_transaction_id' => $txnId,
                'gateway_response' => $row->remarks ? json_encode(['remarks' => $row->remarks]) : null,
                'recharged_by' => null, // dump.user_id is admin from another DB; not safe to map blindly
                'notes' => mb_substr(trim(($row->description ?? '') . ' ' . ($row->source ? "({$row->source})" : '')), 0, 500) ?: null,
                'status' => $status,
                'completed_at' => $status === 'completed' ? $createdAt : null,
                'transaction_id' => null,
                'invoice_id' => null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if ($txnId) {
                $existingTxnIds[$txnId] = true;
            }

            $processed++;

            if (count($batch) >= 500) {
                if (!$dryRun) {
                    DB::table('payments')->insert($batch);
                }
                $this->stats['payments_inserted'] += count($batch);
                $batch = [];
                $this->line("  Payments: {$processed}/{$total}");
            }
        }

        if (!empty($batch)) {
            if (!$dryRun) {
                DB::table('payments')->insert($batch);
            }
            $this->stats['payments_inserted'] += count($batch);
        }

        $this->info("  Payments inserted: {$this->stats['payments_inserted']}, "
            . "skipped duplicates: {$this->stats['payments_skipped_duplicate']}, "
            . "skipped no-user: {$this->stats['payments_skipped_no_user']}");
    }

    /**
     * Map dump's source/processor into our payments.payment_method enum.
     * Production enum: online_stripe, online_paypal, online_sslcommerz,
     * bank_transfer, manual_admin, manual_reseller, bkash.
     */
    private function mapPaymentMethod(?string $source, ?string $processor): string
    {
        $key = strtolower(trim((string) $source));
        return match ($key) {
            'bkash' => 'online_bkash',
            'bank', 'bank transfer' => 'bank_transfer',
            'paypal' => 'online_paypal',
            'sslcommerz', 'ssl', 'ssl commerz' => 'online_sslcommerz',
            'stripe' => 'online_stripe',
            default => 'manual_admin',
        };
    }

    private function dropTempDb(): void
    {
        try {
            DB::statement("DROP DATABASE IF EXISTS `{$this->tempDb}`");
        } catch (\Throwable $e) {
            $this->warn("Could not drop temp DB: {$e->getMessage()}");
        }
    }

    private function printSummary(): void
    {
        $this->newLine();
        $this->info('=== Summary ===');
        $this->table(
            ['Metric', 'Count'],
            collect($this->stats)->map(fn ($v, $k) => [$k, (string) $v])->values()->all()
        );
    }
}

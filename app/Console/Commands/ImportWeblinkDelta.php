<?php

namespace App\Console\Commands;

use App\Models\SipAccount;
use App\Models\User;
use App\Services\SipProvisioningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ImportWeblinkDelta extends Command
{
    protected $signature = 'import:weblink-delta
        {path : Path to weblink SQL dump}
        {--dry-run : Preview without writing}
        {--prune : Phase G — DELETE users not in dump (DESTRUCTIVE)}
        {--prune-confirm : Required alongside --prune}
        {--no-provision : Skip PJSIP re-provisioning}
        {--force : Skip confirmation prompts}';

    protected $description = 'Incrementally update users / SIPs / rates from a WebLink SQL dump (insert if missing, update if exists, optional prune).';

    private string $tempDb = 'weblink_delta';

    /** dump accounts.id → local users.id (positive = real, negative = dry-run placeholder) */
    private array $accountToUserId = [];

    /** dump ratename.id → local rate_groups.id */
    private array $rateGroupMap = [];

    /** sip_accounts.username → ['id' => int, 'user_id' => int] */
    private array $localSipByUsername = [];

    /** sip_account ids that need PJSIP provisioning */
    private array $sipsToProvision = [];

    /** dump accounts.id → cleartext password chosen for the user (= first SIP secret) */
    private array $clientPasswords = [];

    private array $stats = [
        'users_matched_by_sip' => 0,
        'users_matched_by_email' => 0,
        'users_matched_by_username' => 0,
        'users_updated' => 0,
        'users_inserted' => 0,
        'users_skipped' => 0,
        'rate_groups_updated' => 0,
        'rate_groups_inserted' => 0,
        'users_tariff_assigned' => 0,
        'rates_updated' => 0,
        'rates_inserted' => 0,
        'rates_skipped' => 0,
        'sips_updated' => 0,
        'sips_inserted' => 0,
        'sips_skipped' => 0,
        'sips_provisioned' => 0,
        'pruned_users' => 0,
        'prune_exempted_balance' => 0,
        'prune_exempted_cdr' => 0,
    ];

    public function handle(): int
    {
        $path = $this->argument('path');
        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $prune = (bool) $this->option('prune');
        $pruneConfirm = (bool) $this->option('prune-confirm');

        if ($prune && !$pruneConfirm) {
            $this->error('--prune is destructive. Add --prune-confirm to acknowledge.');
            return self::FAILURE;
        }

        $this->info('=== WebLink Delta Import ===');
        $this->line("Source: {$path}");
        $this->line('Mode:   ' . ($dryRun ? 'DRY RUN' : 'LIVE'));
        $this->line('Prune:  ' . ($prune ? 'ENABLED (Phase G will run)' : 'disabled'));
        $this->newLine();

        if (!$dryRun && !$this->option('force')) {
            if (!$this->confirm('Proceed with delta import?')) {
                return self::FAILURE;
            }
        }

        try {
            $this->phaseA_loadSql($path);
            $this->phaseB_buildMatchMap();
            $this->phaseC_upsertUsers($dryRun);
            $this->phaseD_upsertRateGroups($dryRun);
            $this->phaseC1_assignTariffsToUsers($dryRun);
            $this->phaseE_upsertRates($dryRun);
            $this->phaseF_upsertSipAccounts($dryRun);

            if (!$dryRun && !$this->option('no-provision') && !empty($this->sipsToProvision)) {
                $this->reloadPjsipOnce();
            }

            if ($prune) {
                $this->phaseG_prune($dryRun);
            }

            if (!$dryRun && ($this->stats['users_inserted'] > 0 || $this->stats['pruned_users'] > 0)) {
                $this->info('Rebuilding user hierarchy paths...');
                User::rebuildAllHierarchyPaths();
            }

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

    // ── Phase A ─────────────────────────────────────────────────────────────

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

        $env = $password
            ? ['MYSQL_PWD' => $password, 'PATH' => getenv('PATH') ?: '/usr/bin:/usr/local/bin']
            : null;

        $descriptors = [
            0 => ['file', $path, 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, null, $env);
        if (!is_resource($process)) {
            throw new \RuntimeException('Failed to start mysql import process');
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

    // ── Phase B ─────────────────────────────────────────────────────────────

    private function phaseB_buildMatchMap(): void
    {
        $this->info('Phase B: Building user match map (SIP-pivot, then email fallback)...');

        // Cache local SIP accounts: username → user_id
        foreach (DB::table('sip_accounts')->select('id', 'username', 'user_id')->get() as $sip) {
            $this->localSipByUsername[$sip->username] = ['id' => $sip->id, 'user_id' => $sip->user_id];
        }
        $this->line('  Local SIPs cached: ' . count($this->localSipByUsername));

        // Pass 1: SIP-username pivot
        $rows = DB::select(
            "SELECT id_client, name FROM `{$this->tempDb}`.`sipusers` WHERE type='friend' AND status != -1"
        );
        foreach ($rows as $row) {
            $username = trim($row->name);
            $clientId = (int) $row->id_client;
            if ($username === '' || $clientId <= 0) continue;
            if (isset($this->accountToUserId[$clientId])) continue;
            if (isset($this->localSipByUsername[$username])) {
                $this->accountToUserId[$clientId] = $this->localSipByUsername[$username]['user_id'];
                $this->stats['users_matched_by_sip']++;
            }
        }
        $this->line('  Matched via SIP pivot:    ' . $this->stats['users_matched_by_sip']);

        // Pass 2: email fallback for unmatched dump-accounts
        $accounts = DB::select(
            "SELECT id, email FROM `{$this->tempDb}`.`accounts`
             WHERE status != -1 AND account_type IN (1,2,3,4,5)
             GROUP BY id"
        );
        foreach ($accounts as $a) {
            $accountId = (int) $a->id;
            if (isset($this->accountToUserId[$accountId])) continue;

            $email = $this->sanitizeEmailForMatch($a->email);
            if ($email === null) continue;

            $userId = DB::table('users')->whereRaw('LOWER(email) = ?', [strtolower($email)])->value('id');
            if ($userId) {
                $this->accountToUserId[$accountId] = $userId;
                $this->stats['users_matched_by_email']++;
            }
        }
        $this->line('  Matched via email fallback: ' . $this->stats['users_matched_by_email']);

        // Pass 3: Username collision — handles source-data dups where two
        // accounts.id share the same (trimmed) username. Without this pass,
        // the second source-account-with-same-username falls through to INSERT
        // and re-creates a ghost user that we just merged.
        $accounts = DB::select(
            "SELECT id, username FROM `{$this->tempDb}`.`accounts`
             WHERE status != -1 AND account_type IN (1,2,3,4,5)
             GROUP BY id"
        );
        foreach ($accounts as $a) {
            $accountId = (int) $a->id;
            if (isset($this->accountToUserId[$accountId])) continue;

            $username = trim((string) ($a->username ?? ''));
            if ($username === '') continue;

            $userId = DB::table('users')
                ->where('username', $username)
                ->whereIn('role', ['reseller', 'client'])
                ->value('id');

            if ($userId) {
                $this->accountToUserId[$accountId] = $userId;
                $this->stats['users_matched_by_username']++;
            }
        }
        $this->line('  Matched via username collision: ' . $this->stats['users_matched_by_username']);
        $this->line('  Total matched:            ' . count($this->accountToUserId));

        // Build clientId → cleartext password (= first SIP secret encountered).
        // Used by Phase C to set the user's password and by Phase F to make
        // ALL of that client's SIP accounts share the same password.
        $secretRows = DB::select(
            "SELECT id_client, secret FROM `{$this->tempDb}`.`sipusers`
             WHERE type='friend' AND status != -1
               AND secret IS NOT NULL AND secret <> ''
             ORDER BY id"
        );
        foreach ($secretRows as $row) {
            $clientId = (int) $row->id_client;
            if ($clientId <= 0 || isset($this->clientPasswords[$clientId])) continue;
            $this->clientPasswords[$clientId] = $row->secret;
        }
        $this->line('  Accounts with SIP-derived password: ' . count($this->clientPasswords));
    }

    private function sanitizeEmailForMatch(?string $raw): ?string
    {
        $email = trim((string) $raw);
        if ($email === '') return null;

        // Reject mangled emails created by the previous full import
        if (str_ends_with(strtolower($email), '@weblink.import')) return null;
        if (preg_match('/\+\d+@/', $email)) return null;

        $email = preg_replace('/_del_[A-Z]$/', '', $email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    // ── Phase C ─────────────────────────────────────────────────────────────

    private function phaseC_upsertUsers(bool $dryRun): void
    {
        $this->info('Phase C: Upserting users (reseller + client only)...');

        $rows = DB::select(
            "SELECT * FROM `{$this->tempDb}`.`accounts`
             WHERE status != -1 AND account_type IN (1,2,3,4,5)
             GROUP BY id ORDER BY id"
        );

        // Default for users without a SIP-derived password (most resellers).
        // Stored as $md5$ wrapper so the compat provider transparently upgrades
        // to bcrypt on first successful login (fast import, full security later).
        $defaultPassword = '$md5$' . md5('ChangeMe123!');
        $usedEmails = [];
        $usedUsernames = [];

        // Pre-load existing emails for dedup
        $existingEmails = [];
        foreach (DB::table('users')->pluck('email') as $e) {
            $existingEmails[strtolower($e)] = true;
        }

        // Pre-load existing usernames so we don't violate the UNIQUE constraint
        $existingUsernames = [];
        foreach (DB::table('users')->whereNotNull('username')->pluck('username') as $u) {
            $existingUsernames[$u] = true;
        }

        $total = count($rows);
        $processed = 0;

        foreach ($rows as $row) {
            $accountId = (int) $row->id;
            $role = $this->mapRole((int) $row->account_type);
            if ($role === null) {
                $this->stats['users_skipped']++;
                continue;
            }

            if (isset($this->accountToUserId[$accountId]) && $this->accountToUserId[$accountId] > 0) {
                // UPDATE existing — only operational fields
                if (!$dryRun) {
                    DB::table('users')->where('id', $this->accountToUserId[$accountId])->update([
                        'balance' => $row->balance ?? 0,
                        'credit_limit' => $row->credit ?? 0,
                        'status' => $row->status == 1 ? 'active' : 'suspended',
                        'billing_type' => $row->prepaid == 1 ? 'prepaid' : 'postpaid',
                        'max_channels' => $row->account_limit > 0 ? $row->account_limit : 10,
                        'updated_at' => now(),
                    ]);
                }
                $this->stats['users_updated']++;
            } else {
                // INSERT new
                $email = $this->buildUniqueEmail($row, $usedEmails, $existingEmails);
                $createdAt = $row->creationdate;
                if (!$createdAt || $createdAt === '0000-00-00 00:00:00') {
                    $createdAt = now()->toDateTimeString();
                }

                $username = $this->resolveUniqueUsername($row, $usedUsernames, $existingUsernames);

                // Per-user password: clients get their first SIP secret (so
                // their panel login matches the password their phone already
                // uses); resellers/no-SIP users fall back to the default.
                $cleartext = $this->clientPasswords[$accountId] ?? null;
                $userPassword = $cleartext !== null
                    ? '$md5$' . md5($cleartext)
                    : $defaultPassword;

                $displayName = trim((string) ($row->account_name ?: $row->username ?: 'User ' . $row->id));

                if (!$dryRun) {
                    $newId = DB::table('users')->insertGetId([
                        'name' => $displayName,
                        'email' => $email,
                        'username' => $username,
                        'password' => $userPassword,
                        'role' => $role,
                        'parent_id' => null, // resolved below
                        'status' => $row->status == 1 ? 'active' : 'suspended',
                        'billing_type' => $row->prepaid == 1 ? 'prepaid' : 'postpaid',
                        'balance' => $row->balance ?? 0,
                        'credit_limit' => $row->credit ?? 0,
                        'max_channels' => $row->account_limit > 0 ? $row->account_limit : 10,
                        'currency' => 'BDT',
                        'kyc_status' => 'approved',
                        'created_at' => $createdAt,
                        'updated_at' => now()->toDateTimeString(),
                    ]);
                    $this->accountToUserId[$accountId] = $newId;
                    if ($email !== null) {
                        $existingEmails[strtolower($email)] = true;
                    }
                    if ($username !== null) {
                        $existingUsernames[$username] = true;
                    }
                } else {
                    // Negative placeholder lets later phases skip cleanly
                    $this->accountToUserId[$accountId] = -$accountId;
                }
                $this->stats['users_inserted']++;
            }

            $processed++;
            if ($processed % 1000 === 0) {
                $this->line("  Users: {$processed}/{$total}");
            }
        }

        // Pass 2: parent_id back-fill, only for newly inserted users (preserve manual hierarchy edits)
        if (!$dryRun) {
            $this->info('  Resolving parent_id for new users...');
            foreach ($rows as $row) {
                $accountId = (int) $row->id;
                $oldParent = (int) ($row->parent_id ?? 0);
                if ($oldParent <= 0 || $oldParent === $accountId) continue;
                if (!isset($this->accountToUserId[$accountId]) || $this->accountToUserId[$accountId] <= 0) continue;
                if (!isset($this->accountToUserId[$oldParent]) || $this->accountToUserId[$oldParent] <= 0) continue;

                DB::table('users')
                    ->where('id', $this->accountToUserId[$accountId])
                    ->whereNull('parent_id')
                    ->update(['parent_id' => $this->accountToUserId[$oldParent]]);
            }
        }

        $this->info("  Users updated: {$this->stats['users_updated']}, inserted: {$this->stats['users_inserted']}, skipped: {$this->stats['users_skipped']}");
    }

    /**
     * Pick a unique username for a new user. Pulls from accounts.username (the
     * WebLink login id, usually phone-number-like). Returns null if blank, too
     * long, or already taken — those users keep email-only login.
     */
    private function resolveUniqueUsername(object $row, array &$usedUsernames, array &$existingUsernames): ?string
    {
        $username = trim((string) ($row->username ?? ''));
        if ($username === '' || mb_strlen($username) > 100) {
            return null;
        }
        if (isset($existingUsernames[$username]) || isset($usedUsernames[$username])) {
            return null;
        }
        $usedUsernames[$username] = true;
        return $username;
    }

    /**
     * Pick a unique email for a new user, or return null if the source has none.
     * The schema now allows NULL email, so accounts without a real email
     * upstream simply land as NULL — no synthetic placeholder required.
     */
    private function buildUniqueEmail(object $row, array &$usedEmails, array &$existingEmails): ?string
    {
        $email = trim((string) ($row->email ?? ''));
        $email = preg_replace('/_del_[A-Z]$/', '', $email);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $key = strtolower($email);
        $base = $email;
        $counter = 1;
        while (isset($usedEmails[$key]) || isset($existingEmails[$key])) {
            [$local, $domain] = array_pad(explode('@', $base, 2), 2, 'weblink.import');
            $email = $local . '+' . $counter . '@' . $domain;
            $key = strtolower($email);
            $counter++;
        }
        $usedEmails[$key] = true;
        return $email;
    }

    private function mapRole(int $accountType): ?string
    {
        return match ($accountType) {
            1, 2, 3, 4 => 'reseller',
            5 => 'client',
            default => null,
        };
    }

    // ── Phase D ─────────────────────────────────────────────────────────────

    private function phaseD_upsertRateGroups(bool $dryRun): void
    {
        $this->info('Phase D: Upserting rate groups...');

        $rows = DB::select("SELECT * FROM `{$this->tempDb}`.`ratename` WHERE status != -1 ORDER BY id");

        $superAdmin = User::where('role', 'super_admin')->first();
        $createdBy = $superAdmin?->id ?? User::first()?->id ?? 1;

        $existing = [];
        foreach (DB::table('rate_groups')->select('id', 'name')->get() as $g) {
            $existing[strtolower(trim($g->name))] = $g->id;
        }

        foreach ($rows as $row) {
            $name = trim($row->description ?: 'Untitled Rate Group');
            $key = strtolower($name);

            if (isset($existing[$key])) {
                $this->rateGroupMap[$row->id] = $existing[$key];
                if (!$dryRun) {
                    DB::table('rate_groups')->where('id', $existing[$key])->update([
                        'description' => $row->notes ?: null,
                        'updated_at' => now(),
                    ]);
                }
                $this->stats['rate_groups_updated']++;
            } else {
                if (!$dryRun) {
                    $newId = DB::table('rate_groups')->insertGetId([
                        'name' => $name,
                        'description' => $row->notes ?: null,
                        'type' => 'admin',
                        'created_by' => $createdBy,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->rateGroupMap[$row->id] = $newId;
                    $existing[$key] = $newId;
                } else {
                    $this->rateGroupMap[$row->id] = -$row->id;
                }
                $this->stats['rate_groups_inserted']++;
            }
        }

        $this->info("  Rate groups updated: {$this->stats['rate_groups_updated']}, inserted: {$this->stats['rate_groups_inserted']}");
    }

    // ── Phase C.5 (after D so rateGroupMap is built) ────────────────────────

    /**
     * Assign each matched user their rate group (tariff) from accounts.id_tariff
     * via the rateGroupMap built in Phase D. Without this, every imported user
     * lands with rate_group_id NULL, which breaks call rating downstream.
     */
    private function phaseC1_assignTariffsToUsers(bool $dryRun): void
    {
        $this->info('Phase C.5: Assigning rate groups (tariffs) from accounts.id_tariff...');

        $rows = DB::select(
            "SELECT id, id_tariff
             FROM `{$this->tempDb}`.`accounts`
             WHERE status != -1
               AND account_type IN (1,2,3,4,5)
               AND id_tariff > 0
             GROUP BY id"
        );

        foreach ($rows as $row) {
            $accountId = (int) $row->id;
            $tariffId = (int) $row->id_tariff;

            $userId = $this->accountToUserId[$accountId] ?? null;
            if ($userId === null || $userId <= 0) continue;

            $rateGroupId = $this->rateGroupMap[$tariffId] ?? null;
            if ($rateGroupId === null || $rateGroupId <= 0) continue;

            if (!$dryRun) {
                DB::table('users')->where('id', $userId)->update(['rate_group_id' => $rateGroupId]);
            }
            $this->stats['users_tariff_assigned']++;
        }

        $this->info("  Tariffs assigned: {$this->stats['users_tariff_assigned']}");
    }

    // ── Phase E ─────────────────────────────────────────────────────────────

    private function phaseE_upsertRates(bool $dryRun): void
    {
        $this->info('Phase E: Upserting rates...');

        $rows = DB::select("SELECT * FROM `{$this->tempDb}`.`ratechart` WHERE status != -1 ORDER BY id");

        $existing = [];
        foreach (DB::table('rates')->select('id', 'rate_group_id', 'prefix')->get() as $r) {
            $existing["{$r->rate_group_id}|{$r->prefix}"] = $r->id;
        }

        foreach ($rows as $row) {
            if (!isset($this->rateGroupMap[$row->rate_id])) {
                $this->stats['rates_skipped']++;
                continue;
            }
            $rateGroupId = $this->rateGroupMap[$row->rate_id];
            $prefix = trim($row->prefix);
            if ($prefix === '') {
                $this->stats['rates_skipped']++;
                continue;
            }

            $effective = $row->activation;
            $effective = (!$effective || $effective === '0000-00-00 00:00:00')
                ? '2024-01-01'
                : date('Y-m-d', strtotime($effective));
            $billingIncrement = max(1, (int) round(($row->pulse * $row->minute_flex) / 60));

            $payload = [
                'destination' => $row->description ?: 'Unknown',
                'rate_per_minute' => $row->rate ?? 0,
                'min_duration' => $row->grace_period ?? 0,
                'billing_increment' => $billingIncrement,
                'effective_date' => $effective,
                'status' => $row->status == 1 ? 'active' : 'disabled',
                'updated_at' => now(),
            ];

            // Dry-run for newly-mapped rate groups: still count as insert, skip DB
            if ($rateGroupId < 0) {
                $this->stats['rates_inserted']++;
                continue;
            }

            $key = "{$rateGroupId}|{$prefix}";
            if (isset($existing[$key])) {
                if (!$dryRun) {
                    DB::table('rates')->where('id', $existing[$key])->update($payload);
                }
                $this->stats['rates_updated']++;
            } else {
                if (!$dryRun) {
                    $newId = DB::table('rates')->insertGetId(array_merge($payload, [
                        'rate_group_id' => $rateGroupId,
                        'prefix' => $prefix,
                        'connection_fee' => 0,
                        'end_date' => null,
                        'created_at' => now(),
                    ]));
                    $existing[$key] = $newId;
                }
                $this->stats['rates_inserted']++;
            }
        }

        $this->info("  Rates updated: {$this->stats['rates_updated']}, inserted: {$this->stats['rates_inserted']}, skipped: {$this->stats['rates_skipped']}");
    }

    // ── Phase F ─────────────────────────────────────────────────────────────

    private function phaseF_upsertSipAccounts(bool $dryRun): void
    {
        $this->info('Phase F: Upserting SIP accounts (active-only — status=1)...');

        // Only sync ACTIVE SIPs from source. Suspended sipusers (status=0)
        // are deliberately excluded — they accumulate as dead-weight realtime
        // entries that Asterisk has to maintain, and they don't represent
        // callable customers.
        $rows = DB::select("SELECT * FROM `{$this->tempDb}`.`sipusers` WHERE type='friend' AND status = 1 ORDER BY id");

        foreach ($rows as $row) {
            $username = trim($row->name);
            $clientId = (int) $row->id_client;
            if ($username === '') {
                $this->stats['sips_skipped']++;
                continue;
            }

            $userId = $this->accountToUserId[$clientId] ?? null;
            if ($userId === null || $userId <= 0) {
                $this->stats['sips_skipped']++;
                continue;
            }

            [$callerIdName, $callerIdNumber] = $this->parseCallerId($row->callerid ?? '', $username);
            $maxChannels = (int) ($row->{'call-limit'} ?? 2);
            $status = $row->status == 1 ? 'active' : 'suspended';

            // Unified password rule: every SIP for a given client uses the
            // SAME password as the user themselves. The user's password is
            // derived from the FIRST SIP secret found in Phase B; here we
            // override the per-row secret so all of this client's SIPs match.
            $sipPassword = $this->clientPasswords[$clientId] ?? $row->secret ?: SipProvisioningService::generatePassword();

            if (isset($this->localSipByUsername[$username])) {
                $sipId = $this->localSipByUsername[$username]['id'];
                if (!$dryRun) {
                    $update = [
                        'caller_id_name' => $callerIdName,
                        'caller_id_number' => $callerIdNumber,
                        'max_channels' => $maxChannels,
                        'status' => $status,
                        'password' => $sipPassword,
                        'updated_at' => now(),
                    ];
                    DB::table('sip_accounts')->where('id', $sipId)->update($update);
                    $this->sipsToProvision[$sipId] = true;
                }
                $this->stats['sips_updated']++;
            } else {
                if (!$dryRun) {
                    $sipId = DB::table('sip_accounts')->insertGetId([
                        'user_id' => $userId,
                        'username' => $username,
                        'password' => $sipPassword,
                        'auth_type' => 'password',
                        'caller_id_name' => $callerIdName,
                        'caller_id_number' => $callerIdNumber,
                        'max_channels' => $maxChannels,
                        'codec_allow' => 'ulaw,alaw,g729',
                        'allow_p2p' => (bool) ($row->allow_p2p_call ?? 0),
                        'allow_recording' => (bool) ($row->allow_call_record ?? 0),
                        'status' => $status,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->sipsToProvision[$sipId] = true;
                    $this->localSipByUsername[$username] = ['id' => $sipId, 'user_id' => $userId];
                }
                $this->stats['sips_inserted']++;
            }
        }

        // Provision changed/new SIPs (skip AMI reload — done once at end)
        if (!$dryRun && !$this->option('no-provision') && !empty($this->sipsToProvision)) {
            $this->info('  Provisioning ' . count($this->sipsToProvision) . ' SIPs to PJSIP realtime...');
            $service = app(SipProvisioningService::class);
            $sips = SipAccount::whereIn('id', array_keys($this->sipsToProvision))->get();
            foreach ($sips as $sip) {
                try {
                    $service->provision($sip, skipReload: true);
                    $this->stats['sips_provisioned']++;
                } catch (\Throwable $e) {
                    $this->warn("    Failed to provision '{$sip->username}': {$e->getMessage()}");
                }
            }
        }

        $this->info("  SIPs updated: {$this->stats['sips_updated']}, inserted: {$this->stats['sips_inserted']}, skipped: {$this->stats['sips_skipped']}");
    }

    private function parseCallerId(string $raw, string $fallback): array
    {
        $raw = trim($raw);
        if (preg_match('/^"?([^"<]*)"?\s*<([^>]+)>/', $raw, $m)) {
            $name = substr(trim($m[1]), 0, 80);
            $num = substr(trim($m[2]), 0, 20);
            return [$name !== '' ? $name : $fallback, $num !== '' ? $num : $fallback];
        }
        $num = substr($raw !== '' ? $raw : $fallback, 0, 20);
        return [substr($fallback, 0, 80), $num];
    }

    private function reloadPjsipOnce(): void
    {
        $this->info('Reloading PJSIP (one-shot)...');
        try {
            app(SipProvisioningService::class)->reloadPjsip();
            $this->info('PJSIP reloaded.');
        } catch (\Throwable $e) {
            $this->warn("PJSIP reload failed: {$e->getMessage()} — reload manually on Asterisk.");
        }
    }

    // ── Phase G (DESTRUCTIVE) ───────────────────────────────────────────────

    private function phaseG_prune(bool $dryRun): void
    {
        $this->newLine();
        $this->warn('Phase G: PRUNE — deleting users not present in dump (DESTRUCTIVE).');

        $matchedIds = array_unique(array_filter(array_values($this->accountToUserId), fn ($id) => $id > 0));

        $candidates = DB::table('users')
            ->whereIn('role', ['reseller', 'client'])
            ->when(!empty($matchedIds), fn ($q) => $q->whereNotIn('id', $matchedIds))
            ->pluck('id')
            ->all();

        $totalUsers = DB::table('users')->whereIn('role', ['reseller', 'client'])->count();

        $this->line('  Candidates (not in dump): ' . count($candidates));
        if (empty($candidates)) {
            $this->info('  Nothing to prune.');
            return;
        }

        // Exemption 1 — non-zero balance
        $balanceExempt = DB::table('users')
            ->whereIn('id', $candidates)
            ->where('balance', '>', 0)
            ->pluck('id')
            ->all();
        $exemptIds = array_flip($balanceExempt);
        $this->stats['prune_exempted_balance'] = count($balanceExempt);

        // Exemption 2 — recent CDR (last 30d). call_records partitioned, no FK.
        $cutoff = now()->subDays(30)->toDateTimeString();
        $cdrPool = array_diff($candidates, array_keys($exemptIds));
        if (!empty($cdrPool)) {
            foreach (array_chunk(array_values($cdrPool), 1000) as $chunk) {
                $hits = DB::table('call_records')
                    ->select('user_id')
                    ->whereIn('user_id', $chunk)
                    ->where('call_start', '>=', $cutoff)
                    ->groupBy('user_id')
                    ->pluck('user_id')
                    ->all();
                foreach ($hits as $id) {
                    if (!isset($exemptIds[$id])) {
                        $exemptIds[$id] = true;
                        $this->stats['prune_exempted_cdr']++;
                    }
                }
            }
        }

        $toPrune = array_values(array_diff($candidates, array_keys($exemptIds)));
        $this->line('  Exempted (balance > 0):     ' . $this->stats['prune_exempted_balance']);
        $this->line('  Exempted (recent CDR/30d):  ' . $this->stats['prune_exempted_cdr']);
        $this->line('  Final prune set:            ' . count($toPrune));

        if (empty($toPrune)) {
            $this->info('  Nothing to prune after exemptions.');
            return;
        }

        // Threshold check
        $pct = $totalUsers > 0 ? (count($toPrune) / $totalUsers) * 100 : 0;
        $this->line(sprintf('  Threshold: %.2f%% of %d total reseller+client users (limit 5%%)', $pct, $totalUsers));
        if ($pct > 5.0) {
            $this->error('  ABORT: prune set exceeds 5% threshold.');
            return;
        }

        if ($dryRun) {
            $this->info('  DRY RUN: would delete ' . count($toPrune) . ' users.');
            $previewIds = array_slice($toPrune, 0, 20);
            $preview = DB::table('users')
                ->whereIn('id', $previewIds)
                ->select('id', 'name', 'email', 'role', 'balance')
                ->get();
            $this->table(
                ['ID', 'Name', 'Email', 'Role', 'Balance'],
                $preview->map(fn ($u) => [(string) $u->id, $u->name, $u->email, $u->role, (string) $u->balance])->all()
            );
            return;
        }

        if (!$this->option('force')) {
            $msg = 'DELETE ' . count($toPrune) . ' users + ALL their history (audit_logs, transactions, invoices, payments, broadcasts, voice_files, SIPs, KYC profiles)? Irreversible.';
            if (!$this->confirm($msg)) {
                $this->warn('  Skipped Phase G by user.');
                return;
            }
        }

        // Pre-deprovision PJSIP for affected SIPs
        if (!$this->option('no-provision')) {
            $sips = SipAccount::whereIn('user_id', $toPrune)->get();
            $service = app(SipProvisioningService::class);
            foreach ($sips as $sip) {
                try {
                    $service->deprovision($sip);
                } catch (\Throwable $e) {
                    Log::warning("[ImportWeblinkDelta] deprovision failed", ['sip' => $sip->username, 'err' => $e->getMessage()]);
                }
            }
        }

        // Cascade-wipe history in chunks (keeps IN clauses + transactions reasonable)
        foreach (array_chunk($toPrune, 500) as $chunk) {
            DB::transaction(function () use ($chunk) {
                DB::table('broadcasts')                                                 // → broadcast_numbers
                    ->where(fn ($q) => $q->whereIn('user_id', $chunk)->orWhereIn('created_by', $chunk))
                    ->delete();
                DB::table('survey_templates')->whereIn('user_id', $chunk)->delete();
                DB::table('voice_files')->whereIn('user_id', $chunk)->delete();
                DB::table('destination_lists')->whereIn('user_id', $chunk)->delete();
                DB::table('payments')->whereIn('user_id', $chunk)->delete();
                DB::table('invoices')->whereIn('user_id', $chunk)->delete();           // → invoice_items
                DB::table('transactions')->whereIn('user_id', $chunk)->delete();
                DB::table('audit_logs')->whereIn('user_id', $chunk)->delete();
                DB::table('dnc_numbers')->whereIn('added_by', $chunk)->delete();
                // Finally users — cascades sip_accounts, kyc_profiles, webhook_endpoints, admin_resellers
                DB::table('users')->whereIn('id', $chunk)->delete();
            });
            Log::info('[ImportWeblinkDelta] Pruned chunk', ['count' => count($chunk), 'sample' => array_slice($chunk, 0, 5)]);
        }

        $this->stats['pruned_users'] = count($toPrune);
        $this->info("  Pruned: {$this->stats['pruned_users']} users.");
    }

    // ── Cleanup + summary ───────────────────────────────────────────────────

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
        $this->info('=== Delta Import Summary ===');
        $this->table(
            ['Metric', 'Count'],
            collect($this->stats)
                ->map(fn ($v, $k) => [$k, (string) $v])
                ->values()
                ->all()
        );
    }
}

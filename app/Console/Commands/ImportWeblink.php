<?php

namespace App\Console\Commands;

use App\Models\Rate;
use App\Models\RateGroup;
use App\Models\SipAccount;
use App\Models\Trunk;
use App\Models\TrunkRoute;
use App\Models\User;
use App\Services\SipProvisioningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ImportWeblink extends Command
{
    protected $signature = 'import:weblink
        {path : Path to weblink.sql file}
        {--dry-run : Preview counts without importing}
        {--force : Skip confirmation prompt}';

    protected $description = 'Import data from old WebLink billing system (rbilling_iptsp) into rSwitch';

    private string $tempDb = 'weblink_import';

    // Old ID → New ID mappings
    private array $userMap = [];
    private array $rateGroupMap = [];
    private array $trunkMap = [];       // old sipusers.id (peer) → new trunks.id
    private array $gatewayMap = [];     // old accounts.id (gateway) → new trunks.id

    // Counters
    private array $counts = [
        'rate_groups' => 0,
        'rates' => 0,
        'users' => 0,
        'users_skipped' => 0,
        'trunks' => 0,
        'trunk_routes' => 0,
        'sip_accounts' => 0,
        'sip_provisioned' => 0,
    ];

    public function handle(): int
    {
        $path = $this->argument('path');

        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN - no data will be imported');
        }

        $this->info('=== WebLink to rSwitch Data Migration ===');
        $this->newLine();

        try {
            // Step 1: Import SQL into temp database
            $this->importSqlToTempDb($path);

            if ($dryRun) {
                $this->printSourceCounts();
                $this->dropTempDb();
                return self::SUCCESS;
            }

            // Step 2: Truncate rSwitch tables
            if (!$this->option('force') && !$this->confirm('This will TRUNCATE all existing rSwitch data. Continue?')) {
                $this->dropTempDb();
                $this->warn('Aborted.');
                return self::FAILURE;
            }
            $this->truncateRswitchTables();

            // Step 3: Import users first (needed as FK for rate_groups.created_by)
            $this->importUsers();

            // Step 4: Import rate groups (needs super_admin user for created_by)
            $this->importRateGroups();

            // Step 4b: Assign rate_group_id to users now that rate groups exist
            $this->assignRateGroupsToUsers();

            // Step 5: Import rates
            $this->importRates();

            // Step 6: Import trunks (peers)
            $this->importTrunks();

            // Step 7: Import trunk routes (dialplan)
            $this->importTrunkRoutes();

            // Step 8: Import SIP accounts (friends) + provision
            $this->importSipAccounts();

            // Step 9: Rebuild hierarchy paths
            $this->info('Rebuilding user hierarchy paths...');
            User::rebuildAllHierarchyPaths();
            $this->info('Hierarchy paths rebuilt.');

            // Step 10: Drop temp database
            $this->dropTempDb();

            // Print summary
            $this->printSummary();

            $this->newLine();
            $this->info('Import completed successfully!');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Import failed: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            $this->dropTempDb();
            return self::FAILURE;
        }
    }

    private function importSqlToTempDb(string $path): void
    {
        $this->info('Step 1: Importing weblink.sql into temporary database...');

        $connection = config('database.default');
        $host = config("database.connections.{$connection}.host");
        $port = config("database.connections.{$connection}.port");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");

        // Drop and recreate temp database to ensure clean state
        DB::statement("DROP DATABASE IF EXISTS `{$this->tempDb}`");
        DB::statement("CREATE DATABASE `{$this->tempDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Import SQL file using mysql CLI with MYSQL_PWD env var for safe password handling
        $cmd = sprintf(
            'mysql -h %s -P %s -u %s %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($this->tempDb)
        );

        $env = null;
        if ($password) {
            $env = ['MYSQL_PWD' => $password, 'PATH' => getenv('PATH') ?: '/usr/bin:/usr/local/bin'];
        }

        $descriptors = [
            0 => ['file', $path, 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, null, $env);

        if (!is_resource($process)) {
            throw new \RuntimeException('Failed to start mysql import process');
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new \RuntimeException("MySQL import failed (exit code {$exitCode}): {$stderr}");
        }

        $this->info('SQL imported into temporary database.');
    }

    private function printSourceCounts(): void
    {
        $this->info('Source data counts (excluding deleted records):');

        $accounts = DB::select("SELECT COUNT(*) as cnt FROM `{$this->tempDb}`.`accounts` WHERE status != -1");
        $ratenames = DB::select("SELECT COUNT(*) as cnt FROM `{$this->tempDb}`.`ratename` WHERE status != -1");
        $ratecharts = DB::select("SELECT COUNT(*) as cnt FROM `{$this->tempDb}`.`ratechart` WHERE status != -1");
        $friends = DB::select("SELECT COUNT(*) as cnt FROM `{$this->tempDb}`.`sipusers` WHERE type = 'friend' AND status != -1");
        $peers = DB::select("SELECT COUNT(*) as cnt FROM `{$this->tempDb}`.`sipusers` WHERE type = 'peer' AND status != -1");
        $dialplans = DB::select("SELECT COUNT(*) as cnt FROM `{$this->tempDb}`.`dialplan` WHERE call_flow IN (2, 5) AND status != -1");

        $this->table(
            ['Table', 'Records'],
            [
                ['accounts → users', $accounts[0]->cnt],
                ['ratename → rate_groups', $ratenames[0]->cnt],
                ['ratechart → rates', $ratecharts[0]->cnt],
                ['sipusers (friend) → sip_accounts', $friends[0]->cnt],
                ['sipusers (peer) → trunks', $peers[0]->cnt],
                ['dialplan (flow 2,5) → trunk_routes', $dialplans[0]->cnt],
            ]
        );
    }

    private function truncateRswitchTables(): void
    {
        $this->info('Step 2: Truncating rSwitch tables...');

        Schema::disableForeignKeyConstraints();

        // PJSIP realtime tables
        DB::table('ps_endpoint_id_ips')->truncate();
        DB::table('ps_contacts')->truncate();
        DB::table('ps_aors')->truncate();
        DB::table('ps_auths')->truncate();
        DB::table('ps_endpoints')->truncate();

        // Application tables
        DB::table('trunk_routes')->truncate();
        DB::table('trunks')->truncate();
        DB::table('sip_accounts')->truncate();
        DB::table('rates')->truncate();
        DB::table('rate_groups')->truncate();
        DB::table('transactions')->truncate();
        DB::table('admin_resellers')->truncate();

        // Clear call_records if exists
        if (Schema::hasTable('call_records')) {
            DB::table('call_records')->truncate();
        }

        // Users last (most things reference it)
        DB::table('users')->truncate();

        Schema::enableForeignKeyConstraints();

        $this->info('Tables truncated.');
    }

    private function importRateGroups(): void
    {
        $this->info('Step 4: Importing rate groups...');

        // Find the first super_admin user for created_by
        $superAdmin = User::where('role', 'super_admin')->first();
        $createdBy = $superAdmin ? $superAdmin->id : User::first()->id;

        $rows = DB::select("SELECT * FROM `{$this->tempDb}`.`ratename` WHERE status != -1 ORDER BY id");

        foreach ($rows as $row) {
            $rateGroup = RateGroup::create([
                'name' => $row->description ?: 'Untitled Rate Group',
                'description' => $row->notes ?: null,
                'type' => 'admin',
                'created_by' => $createdBy,
            ]);

            $this->rateGroupMap[$row->id] = $rateGroup->id;
            $this->counts['rate_groups']++;
        }

        $this->info("Imported {$this->counts['rate_groups']} rate groups.");
    }

    private function assignRateGroupsToUsers(): void
    {
        $this->info('Assigning rate groups to users...');

        $rows = DB::select("SELECT id, id_tariff FROM `{$this->tempDb}`.`accounts` WHERE status != -1 AND id_tariff > 0 GROUP BY id");

        foreach ($rows as $row) {
            if (!isset($this->userMap[$row->id]) || !isset($this->rateGroupMap[$row->id_tariff])) {
                continue;
            }

            User::withoutEvents(function () use ($row) {
                User::where('id', $this->userMap[$row->id])
                    ->update(['rate_group_id' => $this->rateGroupMap[$row->id_tariff]]);
            });
        }
    }

    private function importUsers(): void
    {
        $this->info('Step 3: Importing users...');

        // Use GROUP BY to deduplicate source IDs (MyISAM source has no PK, duplicates exist)
        $rows = DB::select("SELECT * FROM `{$this->tempDb}`.`accounts` WHERE status != -1 GROUP BY id ORDER BY id");

        // Old passwords are MD5 hashes - bcrypt one default password for all users.
        // Users will need to reset passwords after migration.
        $defaultPassword = Hash::make('ChangeMe123!');
        $this->warn('All users will get a default password (ChangeMe123!) - password resets required after migration.');

        // Track emails to handle duplicates
        $usedEmails = [];
        $total = count($rows);
        $processed = 0;

        Schema::disableForeignKeyConstraints();

        foreach ($rows as $row) {
            $role = $this->mapAccountTypeToRole($row->account_type);
            if ($role === null) {
                $this->warn("Skipping account ID {$row->id}: unknown account_type {$row->account_type}");
                $this->counts['users_skipped']++;
                continue;
            }

            // Handle email: generate unique if empty or duplicate
            $email = trim($row->email);
            if (empty($email)) {
                $email = 'user_' . $row->id . '@weblink.import';
            }

            // Strip _del_A suffix from email (deleted account markers)
            $email = preg_replace('/_del_[A-Z]$/', '', $email);

            // Deduplicate: check both in-memory tracker and DB
            $emailLower = strtolower($email);
            $baseEmail = $email;
            $counter = 1;
            while (isset($usedEmails[$emailLower]) || DB::table('users')->whereRaw('LOWER(email) = ?', [$emailLower])->exists()) {
                $parts = explode('@', $baseEmail);
                $email = $parts[0] . '+' . $counter . '@' . ($parts[1] ?? 'weblink.import');
                $emailLower = strtolower($email);
                $counter++;
            }
            $usedEmails[$emailLower] = true;

            // Parse created_at - handle invalid dates
            $createdAt = $row->creationdate;
            if (!$createdAt || $createdAt === '0000-00-00 00:00:00') {
                $createdAt = now()->toDateTimeString();
            }

            $newId = DB::table('users')->insertGetId([
                'name' => $row->account_name ?: $row->username ?: 'User ' . $row->id,
                'email' => $email,
                'password' => $defaultPassword,
                'role' => $role,
                'parent_id' => null,
                'status' => $row->status == 1 ? 'active' : 'suspended',
                'billing_type' => $row->prepaid == 1 ? 'prepaid' : 'postpaid',
                'balance' => $row->balance ?? 0,
                'credit_limit' => $row->credit ?? 0,
                'rate_group_id' => null,
                'max_channels' => $row->account_limit > 0 ? $row->account_limit : 10,
                'currency' => 'BDT',
                'kyc_status' => 'approved',
                'created_at' => $createdAt,
                'updated_at' => now()->toDateTimeString(),
            ]);

            $this->userMap[$row->id] = $newId;
            $this->counts['users']++;
            $processed++;

            if ($processed % 1000 === 0) {
                $this->info("  Users: {$processed}/{$total}");
            }
        }

        Schema::enableForeignKeyConstraints();

        // Pass 2: Update parent_id using old→new mapping
        $this->info('Updating parent references...');
        foreach ($rows as $row) {
            if (!isset($this->userMap[$row->id])) {
                continue;
            }

            $oldParentId = $row->parent_id;
            if ($oldParentId > 0 && isset($this->userMap[$oldParentId])) {
                DB::table('users')
                    ->where('id', $this->userMap[$row->id])
                    ->update(['parent_id' => $this->userMap[$oldParentId]]);
            }
        }

        $this->info("Imported {$this->counts['users']} users ({$this->counts['users_skipped']} skipped).");
    }

    private function importRates(): void
    {
        $this->info('Step 5: Importing rates...');

        $rows = DB::select("SELECT * FROM `{$this->tempDb}`.`ratechart` WHERE status != -1 ORDER BY id");

        $batch = [];

        foreach ($rows as $row) {
            if (!isset($this->rateGroupMap[$row->rate_id])) {
                continue; // Rate group was deleted/skipped
            }

            $rateGroupId = $this->rateGroupMap[$row->rate_id];

            // Skip empty prefixes (would match all calls in longest-prefix match)
            if (trim($row->prefix) === '') {
                $this->warn("Skipping rate ID {$row->id}: empty prefix");
                continue;
            }

            // Convert billing_increment: pulse × minute_flex / 60
            $billingIncrement = max(1, (int) round(($row->pulse * $row->minute_flex) / 60));

            // Parse effective_date
            $effectiveDate = $row->activation;
            if (!$effectiveDate || $effectiveDate === '0000-00-00 00:00:00') {
                $effectiveDate = '2024-01-01';
            } else {
                $effectiveDate = date('Y-m-d', strtotime($effectiveDate));
            }

            $batch[] = [
                'rate_group_id' => $rateGroupId,
                'prefix' => trim($row->prefix),
                'destination' => $row->description ?: 'Unknown',
                'rate_per_minute' => $row->rate ?? 0,
                'connection_fee' => 0,
                'min_duration' => $row->grace_period ?? 0,
                'billing_increment' => $billingIncrement,
                'effective_date' => $effectiveDate,
                'end_date' => null,
                'status' => $row->status == 1 ? 'active' : 'disabled',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $this->counts['rates']++;

            // Insert in chunks
            if (count($batch) >= 500) {
                Rate::insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            Rate::insert($batch);
        }

        $this->info("Imported {$this->counts['rates']} rates.");
    }

    private function importTrunks(): void
    {
        $this->info('Step 6: Importing trunks (peer sipusers)...');

        $rows = DB::select("SELECT * FROM `{$this->tempDb}`.`sipusers` WHERE type = 'peer' AND status != -1 ORDER BY id");

        foreach ($rows as $row) {
            $host = trim($row->host ?? $row->name ?? '');
            $name = trim($row->callerid ?? $row->name ?? 'Trunk');
            $port = !empty($row->port) ? (int) $row->port : 5060;

            // Determine direction from context
            $direction = 'outgoing';
            $context = strtolower(trim($row->context ?? ''));
            if (str_contains($context, 'incoming') || str_contains($context, 'from-trunk')) {
                $direction = 'incoming';
            } elseif (str_contains($context, 'both')) {
                $direction = 'both';
            }

            $trunk = Trunk::create([
                'name' => $name ?: 'Trunk-' . $row->id,
                'provider' => trim($row->name ?? '') ?: $host,
                'direction' => $direction,
                'host' => $host ?: '0.0.0.0',
                'port' => $port,
                'username' => $row->fromuser ?: null,
                'password' => $row->secret ?: null,
                'register' => false,
                'transport' => 'udp',
                'max_channels' => $row->{'call-limit'} ?? 30,
                'status' => $row->status == 1 ? 'active' : 'disabled',
                'notes' => $row->note ?? null,
            ]);

            $this->trunkMap[$row->id] = $trunk->id;

            // Also map by gateway account ID (sipusers.id_client → accounts.id)
            // dialplan.gateway_id references accounts.id, not sipusers.id
            if (!empty($row->id_client) && $row->id_client > 0) {
                $this->gatewayMap[$row->id_client] = $trunk->id;
            }

            $this->counts['trunks']++;
        }

        $this->info("Imported {$this->counts['trunks']} trunks.");
    }

    private function importTrunkRoutes(): void
    {
        $this->info('Step 7: Importing trunk routes (dialplan call_flow=2,5)...');

        $rows = DB::select(
            "SELECT * FROM `{$this->tempDb}`.`dialplan` WHERE call_flow IN (2, 5) AND status != -1 ORDER BY id"
        );

        foreach ($rows as $row) {
            // gateway_id references accounts.id (gateway accounts with clienttype=3),
            // NOT sipusers.id directly. Use gatewayMap (accounts.id → trunks.id).
            $trunkId = $this->gatewayMap[$row->gateway_id]
                ?? $this->trunkMap[$row->gateway_id]
                ?? null;

            if (!$trunkId) {
                $this->warn("Skipping dialplan ID {$row->id}: gateway_id {$row->gateway_id} not found in gateway or trunk map");
                continue;
            }

            TrunkRoute::create([
                'trunk_id' => $trunkId,
                'prefix' => trim($row->prefix),
                'priority' => $row->priority ?? 1,
                'weight' => $row->load_share ?? 100,
                'remove_prefix' => !empty($row->remove_prefix) ? trim($row->remove_prefix) : null,
                'add_prefix' => !empty($row->add_prefix) ? trim($row->add_prefix) : null,
                'status' => $row->status == 1 ? 'active' : 'disabled',
            ]);

            $this->counts['trunk_routes']++;
        }

        $this->info("Imported {$this->counts['trunk_routes']} trunk routes.");
    }

    private function importSipAccounts(): void
    {
        $this->info('Step 8: Importing SIP accounts (friend sipusers)...');

        $sipService = app(SipProvisioningService::class);

        $rows = DB::select("SELECT * FROM `{$this->tempDb}`.`sipusers` WHERE type = 'friend' AND status != -1 ORDER BY id");

        foreach ($rows as $row) {
            // Map id_client to new user ID
            if (!isset($this->userMap[$row->id_client])) {
                $this->warn("Skipping SIP account '{$row->name}': owner id_client {$row->id_client} not found in user map");
                continue;
            }

            $userId = $this->userMap[$row->id_client];
            $username = trim($row->name);

            if (empty($username)) {
                $this->warn("Skipping SIP account ID {$row->id}: empty username");
                continue;
            }

            // Check for duplicate usernames
            if (SipAccount::where('username', $username)->exists()) {
                $this->warn("Skipping duplicate SIP username: {$username}");
                continue;
            }

            // Parse caller ID
            $callerId = trim($row->callerid ?? '');
            $callerIdName = '';
            $callerIdNumber = $callerId;

            // If callerid is in format "Name" <number>, parse it
            if (preg_match('/^"?([^"<]*)"?\s*<([^>]+)>/', $callerId, $matches)) {
                $callerIdName = trim($matches[1]);
                $callerIdNumber = trim($matches[2]);
            } elseif (preg_match('/^\d+$/', $callerId)) {
                $callerIdNumber = $callerId;
            }

            // Ensure callerIdNumber is not too long (max 20 chars)
            $callerIdNumber = substr($callerIdNumber, 0, 20);
            $callerIdName = substr($callerIdName, 0, 80);

            $sipAccount = SipAccount::create([
                'user_id' => $userId,
                'username' => $username,
                'password' => $row->secret ?: SipProvisioningService::generatePassword(),
                'auth_type' => 'password',
                'caller_id_name' => $callerIdName ?: $username,
                'caller_id_number' => $callerIdNumber ?: $username,
                'max_channels' => $row->{'call-limit'} ?? 2,
                'codec_allow' => 'ulaw,alaw,g729',
                'allow_p2p' => (bool) ($row->allow_p2p_call ?? 0),
                'allow_recording' => (bool) ($row->allow_call_record ?? 0),
                'status' => $row->status == 1 ? 'active' : 'suspended',
            ]);

            $this->counts['sip_accounts']++;

            // Provision to PJSIP realtime tables (skip AMI reload — do once at end)
            try {
                $sipService->provision($sipAccount, skipReload: true);
                $this->counts['sip_provisioned']++;
            } catch (\Throwable $e) {
                $this->warn("Failed to provision SIP '{$username}': {$e->getMessage()}");
            }
        }

        $this->info("Imported {$this->counts['sip_accounts']} SIP accounts ({$this->counts['sip_provisioned']} provisioned).");

        // Single PJSIP reload at the end (instead of per-account)
        $this->info('Reloading PJSIP...');
        try {
            $sipService->reloadPjsip();
            $this->info('PJSIP reloaded.');
        } catch (\Throwable $e) {
            $this->warn("PJSIP reload failed: {$e->getMessage()} — reload manually on Asterisk.");
        }
    }

    private function mapAccountTypeToRole(int $accountType): ?string
    {
        return match ($accountType) {
            -1 => 'super_admin',
            1, 2, 3, 4 => 'reseller',
            5 => 'client',
            6 => 'recharge_admin',
            default => null,
        };
    }

    private function dropTempDb(): void
    {
        try {
            DB::statement("DROP DATABASE IF EXISTS `{$this->tempDb}`");
            $this->info('Temporary database dropped.');
        } catch (\Throwable $e) {
            $this->warn("Could not drop temp database: {$e->getMessage()}");
        }
    }

    private function printSummary(): void
    {
        $this->newLine();
        $this->info('=== Import Summary ===');

        $this->table(
            ['Table', 'Imported'],
            [
                ['rate_groups', $this->counts['rate_groups']],
                ['rates', $this->counts['rates']],
                ['users', $this->counts['users']],
                ['users (skipped)', $this->counts['users_skipped']],
                ['trunks', $this->counts['trunks']],
                ['trunk_routes', $this->counts['trunk_routes']],
                ['sip_accounts', $this->counts['sip_accounts']],
                ['sip_accounts (provisioned)', $this->counts['sip_provisioned']],
            ]
        );

        // Show user role breakdown
        $this->newLine();
        $this->info('User role breakdown:');
        $roleCounts = User::selectRaw('role, COUNT(*) as cnt')
            ->groupBy('role')
            ->pluck('cnt', 'role')
            ->toArray();
        $this->table(['Role', 'Count'], collect($roleCounts)->map(fn ($cnt, $role) => [$role, $cnt])->values()->toArray());
    }
}

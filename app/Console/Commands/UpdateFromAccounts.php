<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateFromAccounts extends Command
{
    protected $signature = 'import:update-accounts
        {path : Path to accounts SQL dump file}
        {--dry-run : Preview changes without applying}
        {--force : Skip confirmation prompt}';

    protected $description = 'Update user balances and passwords from old WebLink accounts SQL dump (parses SQL directly, no temp DB)';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return 1;
        }

        $this->info('=== Update Balances & Passwords from WebLink Accounts ===');
        $this->newLine();

        // Step 1: Parse SQL file directly
        $this->info('Step 1: Parsing SQL file...');
        $accounts = $this->parseSql($path);
        $this->info('  Parsed ' . count($accounts) . ' accounts from SQL.');

        // Step 2: Match and update
        $this->info('Step 2: Matching accounts to rSwitch users...');

        $matched = 0;
        $notFound = 0;
        $balanceUpdated = 0;
        $passwordUpdated = 0;
        $skipped = [];
        $total = count($accounts);

        foreach ($accounts as $i => $account) {
            $email = trim($account['username']);
            if (empty($email)) {
                continue;
            }

            // Strip _del_A suffix (same as import command)
            $email = preg_replace('/_del_[A-Z]$/', '', $email);

            // Find user by email (case-insensitive)
            $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

            if (!$user) {
                // Try with generated email format: user_{old_id}@weblink.import
                $user = User::where('email', 'user_' . $account['id'] . '@weblink.import')->first();
            }

            if (!$user) {
                $notFound++;
                if ($notFound <= 20) {
                    $skipped[] = $email;
                }
                continue;
            }

            $matched++;

            if ($this->option('dry-run')) {
                if ($user->balance != $account['balance']) {
                    $this->line("  [{$user->email}] Balance: {$user->balance} → {$account['balance']}");
                    $balanceUpdated++;
                }
                $passwordUpdated++;
                continue;
            }

            // Update balance + password
            $updates = [];
            if ($user->balance != $account['balance']) {
                $updates['balance'] = $account['balance'];
                $balanceUpdated++;
            }

            // Store MD5 password with prefix marker for transparent auth
            $md5Password = trim($account['password']);
            if (!empty($md5Password) && strlen($md5Password) === 32) {
                $updates['password'] = '$md5$' . $md5Password;
                $passwordUpdated++;
            }

            if (!empty($updates)) {
                $updates['updated_at'] = now();
                DB::table('users')->where('id', $user->id)->update($updates);
            }

            if (($matched + $notFound) % 2000 === 0) {
                $this->info("  Progress: " . ($matched + $notFound) . "/{$total}");
            }
        }

        $this->newLine();
        $this->info('=== Update Summary ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total accounts in dump', $total],
                ['Matched to rSwitch users', $matched],
                ['Not found', $notFound],
                ['Balances updated', $balanceUpdated],
                ['Passwords updated (MD5)', $passwordUpdated],
            ]
        );

        if (!empty($skipped)) {
            $this->warn('Sample unmatched emails (first 20):');
            foreach ($skipped as $s) {
                $this->line("  - {$s}");
            }
        }

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN — no changes were applied.');
        } else {
            $this->info('Update completed successfully!');
        }

        return 0;
    }

    /**
     * Parse INSERT statements from SQL dump to extract account data.
     */
    private function parseSql(string $path): array
    {
        $accounts = [];
        $content = file_get_contents($path);

        // Find all INSERT INTO `accounts` statements
        // Column order from dump: id, account_type, clienttype, parent_id, acccode, secret_key,
        //   username, password, password_type, api_key, account_name, account_limit, prepaid,
        //   balance, credit, ...
        preg_match_all('/INSERT INTO `accounts`[^;]+;/s', $content, $insertMatches);

        foreach ($insertMatches[0] as $insertBlock) {
            // Extract each VALUES row: (...), (...), ...
            // Match balanced parentheses for each row
            preg_match_all('/\((\d+),\s*(-?\d+),\s*(\d+),\s*(-?\d+),\s*(\d+),\s*(NULL|\'[^\']*\'),\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\d+,\s*\'[^\']*\',\s*\'[^\']*\',\s*\d+,\s*\d+,\s*\'([^\']*)\',\s*\'([^\']*)\'/s', $insertBlock, $rowMatches, PREG_SET_ORDER);

            foreach ($rowMatches as $row) {
                $status = 1; // default active
                // Try to extract status from the full row
                $accounts[] = [
                    'id' => (int) $row[1],
                    'username' => $row[7],  // email
                    'password' => $row[8],  // MD5 hash
                    'balance' => $row[9],   // balance
                ];
            }
        }

        return $accounts;
    }
}

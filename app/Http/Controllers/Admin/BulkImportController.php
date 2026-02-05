<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Did;
use App\Models\SipAccount;
use App\Models\Trunk;
use App\Models\User;
use App\Services\AuditService;
use App\Services\SipProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BulkImportController extends Controller
{
    public function index()
    {
        return view('admin.bulk-import.index');
    }

    /**
     * Import users from CSV.
     * Columns: name, email, password, role, billing_type, balance, rate_group_id, parent_id
     */
    public function importUsers(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $results = ['created' => 0, 'skipped' => 0, 'errors' => []];
        $rows = $this->parseCsv($request->file('csv_file'));

        DB::beginTransaction();

        try {
            foreach ($rows as $i => $row) {
                $line = $i + 2;

                if (empty($row['email']) || empty($row['name'])) {
                    $results['errors'][] = "Line {$line}: missing name or email.";
                    $results['skipped']++;
                    continue;
                }

                if (User::where('email', $row['email'])->exists()) {
                    $results['errors'][] = "Line {$line}: email {$row['email']} already exists.";
                    $results['skipped']++;
                    continue;
                }

                $role = in_array($row['role'] ?? '', ['admin', 'reseller', 'client']) ? $row['role'] : 'client';
                $billingType = in_array($row['billing_type'] ?? '', ['prepaid', 'postpaid']) ? $row['billing_type'] : 'prepaid';

                User::create([
                    'name'         => $row['name'],
                    'email'        => $row['email'],
                    'password'     => Hash::make($row['password'] ?? Str::random(16)),
                    'role'         => $role,
                    'billing_type' => $billingType,
                    'balance'      => $row['balance'] ?? '0.0000',
                    'rate_group_id'=> !empty($row['rate_group_id']) ? (int) $row['rate_group_id'] : null,
                    'parent_id'    => !empty($row['parent_id']) ? (int) $row['parent_id'] : null,
                    'status'       => 'active',
                    'currency'     => 'USD',
                ]);

                $results['created']++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Bulk user import failed', ['error' => $e->getMessage()]);
            return back()->with('warning', "Import failed: {$e->getMessage()}")->withInput();
        }

        AuditService::logAction('bulk_import.users', null, $results);

        return redirect()->route('admin.bulk-import.index')
            ->with('success', "Users imported: {$results['created']} created, {$results['skipped']} skipped.")
            ->with('import_errors', $results['errors']);
    }

    /**
     * Import SIP accounts from CSV.
     * Columns: username, password, user_id, auth_type, allowed_ips, caller_id_name, caller_id_number, max_channels
     */
    public function importSipAccounts(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $results = ['created' => 0, 'skipped' => 0, 'errors' => []];
        $rows = $this->parseCsv($request->file('csv_file'));
        $provisioner = app(SipProvisioningService::class);

        DB::beginTransaction();

        try {
            foreach ($rows as $i => $row) {
                $line = $i + 2;

                if (empty($row['username']) || empty($row['user_id'])) {
                    $results['errors'][] = "Line {$line}: missing username or user_id.";
                    $results['skipped']++;
                    continue;
                }

                if (SipAccount::where('username', $row['username'])->exists()) {
                    $results['errors'][] = "Line {$line}: username {$row['username']} already exists.";
                    $results['skipped']++;
                    continue;
                }

                if (!User::find($row['user_id'])) {
                    $results['errors'][] = "Line {$line}: user_id {$row['user_id']} not found.";
                    $results['skipped']++;
                    continue;
                }

                $authType = in_array($row['auth_type'] ?? '', ['password', 'ip', 'both']) ? $row['auth_type'] : 'password';

                $sipAccount = SipAccount::create([
                    'username'         => $row['username'],
                    'password'         => $row['password'] ?? Str::random(20),
                    'user_id'          => (int) $row['user_id'],
                    'auth_type'        => $authType,
                    'allowed_ips'      => $row['allowed_ips'] ?? null,
                    'caller_id_name'   => $row['caller_id_name'] ?? null,
                    'caller_id_number' => $row['caller_id_number'] ?? null,
                    'max_channels'     => !empty($row['max_channels']) ? (int) $row['max_channels'] : 10,
                    'status'           => 'active',
                ]);

                $provisioner->provision($sipAccount);
                $results['created']++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Bulk SIP import failed', ['error' => $e->getMessage()]);
            return back()->with('warning', "Import failed: {$e->getMessage()}")->withInput();
        }

        AuditService::logAction('bulk_import.sip_accounts', null, $results);

        return redirect()->route('admin.bulk-import.index')
            ->with('success', "SIP accounts imported: {$results['created']} created, {$results['skipped']} skipped.")
            ->with('import_errors', $results['errors']);
    }

    /**
     * Import DIDs from CSV.
     * Columns: number, provider, trunk_id, assigned_to_user_id, destination_type, destination_id, destination_number, monthly_cost, monthly_price
     */
    public function importDids(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $results = ['created' => 0, 'skipped' => 0, 'errors' => []];
        $rows = $this->parseCsv($request->file('csv_file'));

        DB::beginTransaction();

        try {
            foreach ($rows as $i => $row) {
                $line = $i + 2;

                if (empty($row['number']) || empty($row['trunk_id'])) {
                    $results['errors'][] = "Line {$line}: missing number or trunk_id.";
                    $results['skipped']++;
                    continue;
                }

                if (Did::where('number', $row['number'])->exists()) {
                    $results['errors'][] = "Line {$line}: DID {$row['number']} already exists.";
                    $results['skipped']++;
                    continue;
                }

                $trunk = Trunk::find($row['trunk_id']);
                if (!$trunk || !in_array($trunk->direction, ['incoming', 'both'])) {
                    $results['errors'][] = "Line {$line}: trunk_id {$row['trunk_id']} not found or not incoming.";
                    $results['skipped']++;
                    continue;
                }

                $destType = in_array($row['destination_type'] ?? '', ['sip_account', 'ring_group', 'external']) ? $row['destination_type'] : 'sip_account';

                Did::create([
                    'number'              => $row['number'],
                    'provider'            => $row['provider'] ?? $trunk->provider,
                    'trunk_id'            => (int) $row['trunk_id'],
                    'assigned_to_user_id' => !empty($row['assigned_to_user_id']) ? (int) $row['assigned_to_user_id'] : null,
                    'destination_type'    => $destType,
                    'destination_id'      => !empty($row['destination_id']) ? (int) $row['destination_id'] : null,
                    'destination_number'  => $row['destination_number'] ?? null,
                    'monthly_cost'        => $row['monthly_cost'] ?? '0.0000',
                    'monthly_price'       => $row['monthly_price'] ?? '0.0000',
                    'status'              => 'active',
                ]);

                $results['created']++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Bulk DID import failed', ['error' => $e->getMessage()]);
            return back()->with('warning', "Import failed: {$e->getMessage()}")->withInput();
        }

        AuditService::logAction('bulk_import.dids', null, $results);

        return redirect()->route('admin.bulk-import.index')
            ->with('success', "DIDs imported: {$results['created']} created, {$results['skipped']} skipped.")
            ->with('import_errors', $results['errors']);
    }

    /**
     * Parse a CSV file into an array of associative rows.
     */
    private function parseCsv($file): array
    {
        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return [];
        }

        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($headers)) {
                continue;
            }
            $rows[] = array_combine($headers, array_map('trim', $data));
        }

        fclose($handle);
        return $rows;
    }
}

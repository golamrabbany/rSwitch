<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SipAccount;
use App\Models\User;
use App\Services\AuditService;
use App\Services\SipProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SipAccountController extends Controller
{
    public function __construct(
        private SipProvisioningService $provisioning,
    ) {}

    public function index(Request $request)
    {
        $query = SipAccount::with('user');

        // Filter by reseller (show SIP accounts of reseller and their clients)
        if ($request->filled('reseller_id')) {
            $resellerId = $request->reseller_id;
            $clientIds = User::where('parent_id', $resellerId)->pluck('id')->toArray();
            $query->whereIn('user_id', array_merge([$resellerId], $clientIds));
        }

        // Filter by specific client
        if ($request->filled('client_id')) {
            $query->where('user_id', $request->client_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('caller_id_number', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $sipAccounts = $query->orderByDesc('created_at')->paginate(20);

        // Get resellers and clients for filters
        $resellers = User::where('role', 'reseller')->orderBy('name')->get(['id', 'name', 'email']);
        $clients = User::where('role', 'client')->orderBy('name')->get(['id', 'name', 'email', 'parent_id']);

        return view('admin.sip-accounts.index', compact('sipAccounts', 'resellers', 'clients'));
    }

    public function create(Request $request)
    {
        // Only clients can have SIP accounts, not resellers
        $users = User::where('role', 'client')
            ->active()
            ->orderBy('name')
            ->get();

        $selectedUserId = $request->query('user_id');

        return view('admin.sip-accounts.create', compact('users', 'selectedUserId'));
    }

    public function store(Request $request)
    {
        // Get only client IDs for validation
        $clientIds = User::where('role', 'client')->pluck('id')->toArray();

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', Rule::in($clientIds)],
            'username' => ['required', 'string', 'max:40', 'unique:sip_accounts,username', 'alpha_dash'],
            'password' => ['required', 'string', 'min:12', 'max:80'],
            'auth_type' => ['required', Rule::in(['password', 'ip', 'both'])],
            'allowed_ips' => ['nullable', 'required_if:auth_type,ip', 'required_if:auth_type,both', 'string', 'max:500'],
            'caller_id_name' => ['required', 'string', 'max:80'],
            'caller_id_number' => ['required', 'string', 'max:20'],
            'max_channels' => ['required', 'integer', 'min:1', 'max:100'],
            'codec_allow' => ['required', 'string', 'max:100'],
        ]);

        $sip = SipAccount::create($validated);

        // Provision to Asterisk realtime tables
        $this->provisioning->provision($sip);

        AuditService::logCreated($sip, 'sip_account.created');

        return redirect()->route('admin.sip-accounts.show', $sip)
            ->with('success', "SIP account {$sip->username} created and provisioned.");
    }

    public function show(SipAccount $sipAccount)
    {
        $sipAccount->load('user');

        // Check if provisioned in Asterisk
        $provisioned = \DB::table('ps_endpoints')->where('id', $sipAccount->username)->exists();

        return view('admin.sip-accounts.show', compact('sipAccount', 'provisioned'));
    }

    public function edit(SipAccount $sipAccount)
    {
        // Only clients can own SIP accounts
        $users = User::where('role', 'client')
            ->active()
            ->orderBy('name')
            ->get();

        return view('admin.sip-accounts.edit', compact('sipAccount', 'users'));
    }

    public function update(Request $request, SipAccount $sipAccount)
    {
        // Get only client IDs for validation
        $clientIds = User::where('role', 'client')->pluck('id')->toArray();

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', Rule::in($clientIds)],
            'password' => ['nullable', 'string', 'min:12', 'max:80'],
            'auth_type' => ['required', Rule::in(['password', 'ip', 'both'])],
            'allowed_ips' => ['nullable', 'required_if:auth_type,ip', 'required_if:auth_type,both', 'string', 'max:500'],
            'caller_id_name' => ['required', 'string', 'max:80'],
            'caller_id_number' => ['required', 'string', 'max:20'],
            'max_channels' => ['required', 'integer', 'min:1', 'max:100'],
            'codec_allow' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(['active', 'suspended', 'disabled'])],
        ]);

        $original = $sipAccount->getAttributes();

        // Only update password if provided
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $sipAccount->update($validated);

        // Re-provision to Asterisk
        if ($sipAccount->status === 'active') {
            $this->provisioning->provision($sipAccount);
        } else {
            $this->provisioning->deprovision($sipAccount);
        }

        AuditService::logUpdated($sipAccount, $original, 'sip_account.updated');

        return redirect()->route('admin.sip-accounts.show', $sipAccount)
            ->with('success', "SIP account {$sipAccount->username} updated.");
    }

    public function destroy(SipAccount $sipAccount)
    {
        $username = $sipAccount->username;

        // Deprovision from Asterisk first
        $this->provisioning->deprovision($sipAccount);

        AuditService::logAction('sip_account.deleted', $sipAccount, ['username' => $username]);

        $sipAccount->delete();

        return redirect()->route('admin.sip-accounts.index')
            ->with('success', "SIP account {$username} deleted and deprovisioned.");
    }

    /**
     * Re-sync the SIP account to Asterisk realtime tables.
     */
    public function reprovision(SipAccount $sipAccount)
    {
        if ($sipAccount->status === 'active') {
            $this->provisioning->provision($sipAccount);
            $message = "SIP account {$sipAccount->username} re-provisioned.";
        } else {
            $this->provisioning->deprovision($sipAccount);
            $message = "SIP account {$sipAccount->username} deprovisioned (status: {$sipAccount->status}).";
        }

        AuditService::logAction('sip_account.reprovisioned', $sipAccount);

        return back()->with('success', $message);
    }

    /**
     * Export SIP accounts to CSV.
     */
    public function export(Request $request)
    {
        $query = SipAccount::with('user');

        // Apply same filters as index
        if ($request->filled('reseller_id')) {
            $resellerId = $request->reseller_id;
            $clientIds = User::where('parent_id', $resellerId)->pluck('id')->toArray();
            $query->whereIn('user_id', array_merge([$resellerId], $clientIds));
        }

        if ($request->filled('client_id')) {
            $query->where('user_id', $request->client_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('caller_id_number', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $filename = 'sip-accounts-' . now()->format('Y-m-d-His') . '.csv';

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, [
                'username',
                'owner_email',
                'owner_name',
                'owner_role',
                'password',
                'auth_type',
                'allowed_ips',
                'caller_id_name',
                'caller_id_number',
                'max_channels',
                'codec_allow',
                'status',
                'created_at',
            ]);

            // Stream data in chunks
            $query->orderBy('username')->chunk(500, function ($sipAccounts) use ($handle) {
                foreach ($sipAccounts as $sip) {
                    fputcsv($handle, [
                        $sip->username,
                        $sip->user->email,
                        $sip->user->name,
                        $sip->user->role,
                        $sip->password,
                        $sip->auth_type,
                        $sip->allowed_ips,
                        $sip->caller_id_name,
                        $sip->caller_id_number,
                        $sip->max_channels,
                        $sip->codec_allow,
                        $sip->status,
                        $sip->created_at->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Show import form.
     */
    public function importForm()
    {
        return view('admin.sip-accounts.import');
    }

    /**
     * Import SIP accounts from CSV.
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'mode' => ['required', Rule::in(['add', 'update', 'add_update'])],
        ]);

        $file = $request->file('csv_file');
        $mode = $request->mode;

        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        // Validate header
        $requiredColumns = ['username', 'owner_email', 'password', 'auth_type', 'caller_id_name', 'caller_id_number', 'max_channels', 'codec_allow'];
        $missingColumns = array_diff($requiredColumns, $header);

        if (!empty($missingColumns)) {
            fclose($handle);
            return back()->with('error', 'Missing required columns: ' . implode(', ', $missingColumns));
        }

        $headerMap = array_flip($header);
        $results = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        $line = 1;

        \DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $line++;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                $data = [];
                foreach ($header as $index => $column) {
                    $data[$column] = $row[$index] ?? null;
                }

                // Find user by email
                $user = User::where('email', $data['owner_email'])->first();
                if (!$user) {
                    $results['errors'][] = "Line {$line}: User not found with email '{$data['owner_email']}'";
                    $results['skipped']++;
                    continue;
                }

                // Check if SIP account exists
                $existing = SipAccount::where('username', $data['username'])->first();

                if ($existing) {
                    if ($mode === 'add') {
                        $results['errors'][] = "Line {$line}: Username '{$data['username']}' already exists (skipped in add mode)";
                        $results['skipped']++;
                        continue;
                    }

                    // Update existing
                    $existing->update([
                        'user_id' => $user->id,
                        'password' => !empty($data['password']) ? $data['password'] : $existing->password,
                        'auth_type' => $data['auth_type'] ?? $existing->auth_type,
                        'allowed_ips' => $data['allowed_ips'] ?? $existing->allowed_ips,
                        'caller_id_name' => $data['caller_id_name'] ?? $existing->caller_id_name,
                        'caller_id_number' => $data['caller_id_number'] ?? $existing->caller_id_number,
                        'max_channels' => $data['max_channels'] ?? $existing->max_channels,
                        'codec_allow' => $data['codec_allow'] ?? $existing->codec_allow,
                        'status' => $data['status'] ?? $existing->status,
                    ]);

                    // Re-provision
                    if ($existing->status === 'active') {
                        $this->provisioning->provision($existing);
                    }

                    $results['updated']++;
                } else {
                    if ($mode === 'update') {
                        $results['errors'][] = "Line {$line}: Username '{$data['username']}' not found (skipped in update mode)";
                        $results['skipped']++;
                        continue;
                    }

                    // Validate required fields for new account
                    if (empty($data['password']) || strlen($data['password']) < 12) {
                        $results['errors'][] = "Line {$line}: Password must be at least 12 characters";
                        $results['skipped']++;
                        continue;
                    }

                    // Create new
                    $sip = SipAccount::create([
                        'user_id' => $user->id,
                        'username' => $data['username'],
                        'password' => $data['password'],
                        'auth_type' => $data['auth_type'] ?? 'password',
                        'allowed_ips' => $data['allowed_ips'] ?? null,
                        'caller_id_name' => $data['caller_id_name'],
                        'caller_id_number' => $data['caller_id_number'],
                        'max_channels' => $data['max_channels'] ?? 2,
                        'codec_allow' => $data['codec_allow'] ?? 'ulaw,alaw,g729',
                        'status' => $data['status'] ?? 'active',
                    ]);

                    // Provision to Asterisk
                    if ($sip->status === 'active') {
                        $this->provisioning->provision($sip);
                    }

                    $results['created']++;
                }
            }

            fclose($handle);
            \DB::commit();

            AuditService::logAction('sip_accounts.imported', null, [
                'created' => $results['created'],
                'updated' => $results['updated'],
                'skipped' => $results['skipped'],
            ]);

            $message = "Import completed: {$results['created']} created, {$results['updated']} updated, {$results['skipped']} skipped.";

            if (!empty($results['errors'])) {
                return redirect()->route('admin.sip-accounts.index')
                    ->with('success', $message)
                    ->with('warning', 'Some rows had errors: ' . implode('; ', array_slice($results['errors'], 0, 5)) .
                        (count($results['errors']) > 5 ? '... and ' . (count($results['errors']) - 5) . ' more.' : ''));
            }

            return redirect()->route('admin.sip-accounts.index')->with('success', $message);

        } catch (\Exception $e) {
            fclose($handle);
            \DB::rollBack();

            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}

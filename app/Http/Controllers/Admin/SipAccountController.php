<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SipAccount;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AuditService;
use App\Services\SipProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SipAccountController extends Controller
{
    public function __construct(
        private SipProvisioningService $provisioning,
    ) {}

    public function index(Request $request)
    {
        $authUser = auth()->user();
        $query = SipAccount::with('user:id,name,role,email,parent_id');

        // Apply scoping for non-super admins
        if (!$authUser->isSuperAdmin()) {
            $query->whereIn('user_id', $authUser->descendantIds());
        }

        // Filter by reseller (show SIP accounts of reseller and their clients)
        if ($request->filled('reseller_id')) {
            $resellerId = $request->reseller_id;
            // Validate reseller is within scope for non-super admins
            if (!$authUser->isSuperAdmin() && !in_array($resellerId, $authUser->managedResellerIds())) {
                abort(403);
            }
            $clientIds = User::where('parent_id', $resellerId)->pluck('id')->toArray();
            $query->whereIn('user_id', array_merge([(int) $resellerId], $clientIds));
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
                $q->where('username', 'like', "{$search}%")
                  ->orWhere('caller_id_number', 'like', "{$search}%")
                  ->orWhereIn('user_id', User::where('name', 'like', "%{$search}%")->pluck('id'));
            });
        }

        $sipAccounts = $query->orderByDesc('id')->paginate(20);

        // Get resellers for filter (small dataset, safe to preload)
        $resellerQuery = User::where('role', 'reseller')->orderBy('name');
        if (!$authUser->isSuperAdmin()) {
            $resellerQuery->whereIn('id', $authUser->managedResellerIds());
        }
        $resellers = $resellerQuery->get(['id', 'name', 'email']);

        // Resolve selected client name for display
        $selectedClient = $request->filled('client_id')
            ? User::where('id', $request->client_id)->first(['id', 'name', 'email'])
            : null;

        return view('admin.sip-accounts.index', compact('sipAccounts', 'resellers', 'selectedClient'));
    }

    /**
     * AJAX: Search clients for filter dropdown.
     */
    public function searchClients(Request $request)
    {
        $authUser = auth()->user();
        $query = User::where('role', 'client')->orderBy('name');

        if (!$authUser->isSuperAdmin()) {
            $query->whereIn('id', $authUser->clientIds());
        }

        if ($request->filled('reseller_id')) {
            $query->where('parent_id', $request->reseller_id);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "{$search}%")
                  ->orWhere('email', 'like', "{$search}%");
            });
        }

        return response()->json(
            $query->limit(20)->get(['id', 'name', 'email', 'parent_id'])
        );
    }

    /**
     * AJAX: Check registration status for given SIP usernames.
     */
    public function registrationStatus(Request $request)
    {
        $usernames = $request->input('usernames', []);
        if (empty($usernames) || !is_array($usernames)) {
            return response()->json([]);
        }

        $contacts = [];
        try {
            // Use Python billing service API (has real-time AMI connection)
            $response = \Illuminate\Support\Facades\Http::timeout(3)
                ->post('http://127.0.0.1:8001/api/contacts/status', [
                    'usernames' => $usernames,
                ]);

            if ($response->successful()) {
                $contacts = $response->json();
            }
        } catch (\Throwable $e) {
            // Fallback to direct Asterisk CLI if Python API is unavailable
            try {
                $output = shell_exec('sudo asterisk -rx "pjsip show contacts" 2>/dev/null');
                if ($output) {
                    $lookup = array_flip($usernames);
                    foreach (explode("\n", $output) as $line) {
                        if (preg_match('/Contact:\s+(\S+)\/sip:\S+@([\d.]+):\d+\S*\s+\S+\s+(Avail|Unavail)/', $line, $m)) {
                            if (isset($lookup[$m[1]])) {
                                $contacts[$m[1]] = ['ip' => $m[2], 'status' => $m[3]];
                            }
                        }
                    }
                }
            } catch (\Throwable $e2) {
                // Silently fail
            }
        }

        return response()->json($contacts);
    }

    public function create(Request $request)
    {
        $selectedUserId = $request->query('user_id');
        $selectedUser = $selectedUserId
            ? User::where('id', $selectedUserId)->first(['id', 'name', 'email'])
            : null;
        $availableCodecs = SystemSetting::get('default_codec_allow', 'ulaw,alaw,g729');

        return view('admin.sip-accounts.create', compact('selectedUser', 'availableCodecs'));
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();

        // Get only client IDs for validation (scoped for non-super admins)
        $clientQuery = User::where('role', 'client');
        if (!$authUser->isSuperAdmin()) {
            $clientQuery->whereIn('id', $authUser->clientIds());
        }
        $clientIds = $clientQuery->pluck('id')->toArray();

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', Rule::in($clientIds)],
            'username' => ['required', 'string', 'max:40', 'unique:sip_accounts,username', 'alpha_dash'],
            'password' => ['required', 'string', 'min:6', 'max:80'],
            'auth_type' => ['required', Rule::in(['password', 'ip', 'both'])],
            'allowed_ips' => ['nullable', 'required_if:auth_type,ip', 'required_if:auth_type,both', 'string', 'max:500'],
            'caller_id_name' => ['required', 'string', 'max:80'],
            'caller_id_number' => ['required', 'string', 'max:20'],
            'max_channels' => ['required', 'integer', 'min:1', 'max:100'],
            'codec_allow' => ['required', 'string', 'max:100'],
            'allow_p2p' => ['boolean'],
            'allow_recording' => ['boolean'],
        ]);

        // Handle checkbox boolean conversion (unchecked checkboxes send nothing)
        $validated['allow_p2p'] = $request->boolean('allow_p2p');
        $validated['allow_recording'] = $request->boolean('allow_recording');

        // Only super admin can enable random caller ID
        $validated['random_caller_id'] = $authUser->isSuperAdmin() ? $request->boolean('random_caller_id') : false;

        $sip = SipAccount::create($validated);

        // Provision to Asterisk realtime tables
        $this->provisioning->provision($sip);

        AuditService::logCreated($sip, 'sip_account.created');

        return redirect()->route('admin.sip-accounts.show', $sip)
            ->with('success', "SIP account {$sip->username} created and provisioned.");
    }

    public function show(SipAccount $sipAccount)
    {
        $this->authorizeAccess($sipAccount);

        $sipAccount->load('user');

        // Check if provisioned in Asterisk
        $provisioned = \DB::table('ps_endpoints')->where('id', $sipAccount->username)->exists();

        return view('admin.sip-accounts.show', compact('sipAccount', 'provisioned'));
    }

    public function edit(SipAccount $sipAccount)
    {
        $this->authorizeAccess($sipAccount);
        $sipAccount->load('user:id,name,email');

        $availableCodecs = SystemSetting::get('default_codec_allow', 'ulaw,alaw,g729');

        return view('admin.sip-accounts.edit', compact('sipAccount', 'availableCodecs'));
    }

    public function update(Request $request, SipAccount $sipAccount)
    {
        $this->authorizeAccess($sipAccount);

        $authUser = auth()->user();

        // Get only client IDs for validation (scoped for non-super admins)
        $clientQuery = User::where('role', 'client');
        if (!$authUser->isSuperAdmin()) {
            $clientQuery->whereIn('id', $authUser->clientIds());
        }
        $clientIds = $clientQuery->pluck('id')->toArray();

        // Always allow current owner (even if inactive/suspended)
        if (!in_array($sipAccount->user_id, $clientIds)) {
            $clientIds[] = $sipAccount->user_id;
        }

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', Rule::in($clientIds)],
            'password' => ['nullable', 'string', 'min:6', 'max:80'],
            'auth_type' => ['required', Rule::in(['password', 'ip', 'both'])],
            'allowed_ips' => ['nullable', 'required_if:auth_type,ip', 'required_if:auth_type,both', 'string', 'max:500'],
            'caller_id_name' => ['required', 'string', 'max:80'],
            'caller_id_number' => ['required', 'string', 'max:20'],
            'max_channels' => ['required', 'integer', 'min:1', 'max:100'],
            'codec_allow' => ['required', 'string', 'max:100'],
            'allow_p2p' => ['boolean'],
            'allow_recording' => ['boolean'],
            'status' => ['required', Rule::in(['active', 'suspended', 'disabled'])],
        ]);

        // Handle checkbox boolean conversion
        $validated['allow_p2p'] = $request->boolean('allow_p2p');
        $validated['allow_recording'] = $request->boolean('allow_recording');

        // Only super admin can toggle random caller ID
        if ($authUser->isSuperAdmin()) {
            $validated['random_caller_id'] = $request->boolean('random_caller_id');
        }

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
        $this->authorizeAccess($sipAccount);

        $username = $sipAccount->username;

        // Deprovision from Asterisk first
        $this->provisioning->deprovision($sipAccount);

        AuditService::logAction('sip_account.deleted', $sipAccount, ['username' => $username]);

        $sipAccount->delete();

        return redirect()->route('admin.sip-accounts.index')
            ->with('success', "SIP account {$username} deleted and deprovisioned.");
    }

    /**
     * Authorize access to a SIP account for non-super admins.
     */
    private function authorizeAccess(SipAccount $sipAccount): void
    {
        $authUser = auth()->user();

        if ($authUser->isSuperAdmin()) {
            return;
        }

        // Check if the SIP account's owner is within the admin's scope
        $allowedIds = $authUser->descendantIds();
        if (!in_array($sipAccount->user_id, $allowedIds)) {
            abort(403);
        }
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
        $authUser = auth()->user();
        $query = SipAccount::with('user');

        // Apply authorization scoping for non-super admins
        if (!$authUser->isSuperAdmin()) {
            $query->whereIn('user_id', $authUser->descendantIds());
        }

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
        $availableCodecs = SystemSetting::get('default_codec_allow', 'ulaw,alaw,g729');

        return view('admin.sip-accounts.import', compact('availableCodecs'));
    }

    /**
     * Import SIP accounts from XLS/XLSX file.
     */
    public function import(Request $request)
    {
        $authUser = auth()->user();

        // Get only client IDs for validation (scoped for non-super admins)
        $clientQuery = User::where('role', 'client');
        if (!$authUser->isSuperAdmin()) {
            $clientQuery->whereIn('id', $authUser->clientIds());
        }
        $clientIds = $clientQuery->pluck('id')->toArray();

        $validated = $request->validate([
            'xls_file' => ['required', 'file', 'mimes:xls,xlsx', 'max:5120'],
            'mode' => ['required', Rule::in(['add', 'update', 'add_update'])],
            'user_id' => ['required', 'exists:users,id', Rule::in($clientIds)],
            'auth_type' => ['required', Rule::in(['password', 'ip', 'both'])],
            'allowed_ips' => ['nullable', 'required_if:auth_type,ip', 'required_if:auth_type,both', 'string', 'max:500'],
            'max_channels' => ['required', 'integer', 'min:1', 'max:100'],
            'codec_allow' => ['required', 'string', 'max:100'],
            'caller_id_name' => ['nullable', 'string', 'max:80'],
            'caller_id_number' => ['nullable', 'string', 'max:20'],
            'allow_p2p' => ['boolean'],
            'allow_recording' => ['boolean'],
        ]);

        $mode = $validated['mode'];
        $allowP2p = $request->boolean('allow_p2p');
        $allowRecording = $request->boolean('allow_recording');
        $randomCallerId = $authUser->isSuperAdmin() ? $request->boolean('random_caller_id') : false;

        try {
            $spreadsheet = IOFactory::load($request->file('xls_file')->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Could not read the file. Please ensure it is a valid XLS/XLSX file.');
        }

        if (count($rows) < 2) {
            return back()->withInput()->with('error', 'The file appears to be empty (no data rows found).');
        }

        // Parse header row — find username and password columns
        $header = array_map(fn ($v) => strtolower(trim((string) $v)), $rows[1]);
        $colMap = array_flip($header);

        if (!isset($colMap['username']) || !isset($colMap['password'])) {
            return back()->withInput()->with('error', 'Missing required columns: username, password. The first row must contain column headers.');
        }

        $usernameCol = $colMap['username'];
        $passwordCol = $colMap['password'];

        $results = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        \DB::beginTransaction();

        try {
            // Process data rows (skip header at index 1)
            foreach ($rows as $rowNum => $row) {
                if ($rowNum <= 1) {
                    continue; // Skip header
                }

                $username = trim((string) ($row[$usernameCol] ?? ''));
                $password = trim((string) ($row[$passwordCol] ?? ''));

                // Skip empty rows
                if ($username === '' && $password === '') {
                    continue;
                }

                if ($username === '') {
                    $results['errors'][] = "Row {$rowNum}: Username is empty";
                    $results['skipped']++;
                    continue;
                }

                // Check if SIP account exists
                $existing = SipAccount::where('username', $username)->first();

                // Determine caller_id_name and caller_id_number: use form value, fallback to username
                $callerIdName = !empty($validated['caller_id_name']) ? $validated['caller_id_name'] : $username;
                $callerIdNumber = !empty($validated['caller_id_number']) ? $validated['caller_id_number'] : $username;

                if ($existing) {
                    if ($mode === 'add') {
                        $results['errors'][] = "Row {$rowNum}: Username '{$username}' already exists (skipped in add mode)";
                        $results['skipped']++;
                        continue;
                    }

                    // Update existing
                    $updateData = [
                        'user_id' => $validated['user_id'],
                        'auth_type' => $validated['auth_type'],
                        'allowed_ips' => $validated['allowed_ips'] ?? null,
                        'caller_id_name' => $callerIdName,
                        'caller_id_number' => $callerIdNumber,
                        'max_channels' => $validated['max_channels'],
                        'codec_allow' => $validated['codec_allow'],
                        'allow_p2p' => $allowP2p,
                        'allow_recording' => $allowRecording,
                        'random_caller_id' => $randomCallerId,
                    ];

                    // Only update password if provided in the spreadsheet
                    if ($password !== '') {
                        $updateData['password'] = $password;
                    }

                    $existing->update($updateData);

                    if ($existing->status === 'active') {
                        $this->provisioning->provision($existing);
                    }

                    $results['updated']++;
                } else {
                    if ($mode === 'update') {
                        $results['errors'][] = "Row {$rowNum}: Username '{$username}' not found (skipped in update mode)";
                        $results['skipped']++;
                        continue;
                    }

                    if ($password === '' || strlen($password) < 6) {
                        $results['errors'][] = "Row {$rowNum}: Password must be at least 6 characters for new account '{$username}'";
                        $results['skipped']++;
                        continue;
                    }

                    // Validate username format
                    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
                        $results['errors'][] = "Row {$rowNum}: Username '{$username}' contains invalid characters (alphanumeric, dashes, underscores only)";
                        $results['skipped']++;
                        continue;
                    }

                    $sip = SipAccount::create([
                        'user_id' => $validated['user_id'],
                        'username' => $username,
                        'password' => $password,
                        'auth_type' => $validated['auth_type'],
                        'allowed_ips' => $validated['allowed_ips'] ?? null,
                        'caller_id_name' => $callerIdName,
                        'caller_id_number' => $callerIdNumber,
                        'max_channels' => $validated['max_channels'],
                        'codec_allow' => $validated['codec_allow'],
                        'allow_p2p' => $allowP2p,
                        'allow_recording' => $allowRecording,
                        'random_caller_id' => $randomCallerId,
                        'status' => 'active',
                    ]);

                    $this->provisioning->provision($sip);

                    $results['created']++;
                }
            }

            \DB::commit();

            AuditService::logAction('sip_accounts.imported', null, [
                'mode' => $mode,
                'user_id' => $validated['user_id'],
                'created' => $results['created'],
                'updated' => $results['updated'],
                'skipped' => $results['skipped'],
            ]);

            $message = "Import completed: {$results['created']} created, {$results['updated']} updated, {$results['skipped']} skipped.";

            if (!empty($results['errors'])) {
                return redirect()->route('admin.sip-accounts.index')
                    ->with('success', $message)
                    ->with('warning', 'Some rows had issues: ' . implode('; ', array_slice($results['errors'], 0, 5)) .
                        (count($results['errors']) > 5 ? '... and ' . (count($results['errors']) - 5) . ' more.' : ''));
            }

            return redirect()->route('admin.sip-accounts.index')->with('success', $message);

        } catch (\Exception $e) {
            \DB::rollBack();

            return back()->withInput()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Download a sample XLS import template.
     */
    public function importTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('SIP Accounts');

        // Header row
        $sheet->setCellValue('A1', 'username');
        $sheet->setCellValue('B1', 'password');

        // Style header
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
        ];
        $sheet->getStyle('A1:B1')->applyFromArray($headerStyle);

        // Example rows
        $sheet->setCellValue('A2', '100001');
        $sheet->setCellValue('B2', 'StrongP@ss123!');
        $sheet->setCellValue('A3', '100002');
        $sheet->setCellValue('B3', 'An0therP@ss456');

        // Auto-size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        $filename = 'sip-accounts-import-template.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}

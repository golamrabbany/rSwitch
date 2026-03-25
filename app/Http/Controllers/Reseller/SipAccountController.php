<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\SipAccount;
use App\Models\SystemSetting;
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
        // Only show SIP accounts belonging to reseller's clients
        $clientIds = auth()->user()->clientIds();

        $query = SipAccount::whereIn('user_id', $clientIds)->with('user');

        if ($request->filled('user_id') && in_array((int) $request->user_id, $clientIds)) {
            $query->where('user_id', $request->user_id);
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

        // Only show clients in filter dropdown
        $users = User::whereIn('id', $clientIds)->orderBy('name')->get();

        return view('reseller.sip-accounts.index', compact('sipAccounts', 'users'));
    }

    public function create(Request $request)
    {
        // Only clients can have SIP accounts
        $clientIds = auth()->user()->clientIds();
        $users = User::whereIn('id', $clientIds)->active()->orderBy('name')->get();
        $selectedUserId = $request->query('user_id');

        $availableCodecs = SystemSetting::get('default_codec_allow', 'ulaw,alaw,g729');

        return view('reseller.sip-accounts.create', compact('users', 'selectedUserId', 'availableCodecs'));
    }

    public function store(Request $request)
    {
        // Only clients can have SIP accounts
        $clientIds = auth()->user()->clientIds();

        // SIP PIN settings
        $pinPrefix = \App\Models\SystemSetting::get('sip_pin_prefix', '');
        $pinMinLen = \App\Models\SystemSetting::get('sip_pin_min_length', 4);
        $pinMaxLen = \App\Models\SystemSetting::get('sip_pin_max_length', 10);

        $prefixLen = strlen($pinPrefix);
        $totalMinLen = $prefixLen + $pinMinLen;
        $totalMaxLen = $prefixLen + $pinMaxLen;

        $usernameRules = ['required', 'string', 'unique:sip_accounts,username', 'regex:/^\d+$/'];
        $usernameRules[] = "min:{$totalMinLen}";
        $usernameRules[] = "max:{$totalMaxLen}";
        if ($pinPrefix) {
            $usernameRules[] = "starts_with:{$pinPrefix}";
        }

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', Rule::in($clientIds)],
            'username' => $usernameRules,
            'password' => ['required', 'string', 'min:6', 'max:80'],
            'auth_type' => ['required', Rule::in(['password', 'ip', 'both'])],
            'allowed_ips' => ['nullable', 'required_if:auth_type,ip', 'required_if:auth_type,both', 'string', 'max:500'],
            'caller_id_name' => ['required', 'string', 'max:80'],
            'caller_id_number' => ['required', 'string', 'max:20'],
            'max_channels' => ['required', 'integer', 'min:1', 'max:100'],
            'codec_allow' => ['required', 'string', 'max:100'],
        ], [
            'username.regex' => 'PIN must contain only numeric digits.',
            'username.min' => "PIN must be at least {$pinMinLen} digits after prefix '{$pinPrefix}' (total {$totalMinLen}).",
            'username.max' => "PIN must not exceed {$pinMaxLen} digits after prefix '{$pinPrefix}' (total {$totalMaxLen}).",
            'username.starts_with' => "PIN must start with prefix '{$pinPrefix}'.",
            'username.unique' => 'This PIN is already in use.',
        ]);

        // KYC check
        $client = \App\Models\User::find($validated['user_id']);
        if ($client && $client->kyc_status !== 'approved') {
            return back()->withInput()->with('warning', "Cannot create SIP account: {$client->name}'s KYC is not approved.");
        }

        // SIP range check (multiple ranges)
        $reseller = auth()->user();
        $sipRanges = $reseller->sip_ranges;
        if (!empty($sipRanges)) {
            $username = $validated['username'];
            $inRange = false;
            $rangeText = [];
            foreach ($sipRanges as $range) {
                $rangeText[] = $range['start'] . ' — ' . $range['end'];
                if (strcmp($username, $range['start']) >= 0 && strcmp($username, $range['end']) <= 0) {
                    $inRange = true;
                    break;
                }
            }
            if (!$inRange) {
                return back()->withInput()->with('warning', 'PIN must be within your assigned range(s): ' . implode(', ', $rangeText) . '.');
            }
        }

        $sip = SipAccount::create($validated);

        $this->provisioning->provision($sip);

        AuditService::logCreated($sip, 'reseller.sip_account.created');

        if ($request->has('redirect_to_client')) {
            return redirect()->route('reseller.clients.show', $validated['user_id'])
                ->with('success', "SIP account {$sip->username} created and provisioned.");
        }

        return redirect()->route('reseller.sip-accounts.index')
            ->with('success', "SIP account {$sip->username} created and provisioned.");
    }

    public function show(SipAccount $sipAccount)
    {
        abort_unless(in_array($sipAccount->user_id, auth()->user()->clientIds()), 403);

        $sipAccount->load('user');

        $provisioned = \DB::table('ps_endpoints')->where('id', $sipAccount->username)->exists();

        return view('reseller.sip-accounts.show', compact('sipAccount', 'provisioned'));
    }

    public function edit(SipAccount $sipAccount)
    {
        abort_unless(in_array($sipAccount->user_id, auth()->user()->clientIds()), 403);

        // Only clients can own SIP accounts
        $users = User::whereIn('id', auth()->user()->clientIds())->active()->orderBy('name')->get();

        $availableCodecs = SystemSetting::get('default_codec_allow', 'ulaw,alaw,g729');

        return view('reseller.sip-accounts.edit', compact('sipAccount', 'users', 'availableCodecs'));
    }

    public function update(Request $request, SipAccount $sipAccount)
    {
        $clientIds = auth()->user()->clientIds();
        abort_unless(in_array($sipAccount->user_id, $clientIds), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', Rule::in($clientIds)],
            'password' => ['nullable', 'string', 'min:6', 'max:80'],
            'auth_type' => ['required', Rule::in(['password', 'ip', 'both'])],
            'allowed_ips' => ['nullable', 'required_if:auth_type,ip', 'required_if:auth_type,both', 'string', 'max:500'],
            'caller_id_name' => ['required', 'string', 'max:80'],
            'caller_id_number' => ['required', 'string', 'max:20'],
            'max_channels' => ['required', 'integer', 'min:1', 'max:100'],
            'codec_allow' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(['active', 'suspended', 'disabled'])],
        ]);

        $original = $sipAccount->getAttributes();

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $sipAccount->update($validated);

        if ($sipAccount->status === 'active') {
            $this->provisioning->provision($sipAccount);
        } else {
            $this->provisioning->deprovision($sipAccount);
        }

        AuditService::logUpdated($sipAccount, $original, 'reseller.sip_account.updated');

        return redirect()->route('reseller.sip-accounts.index')
            ->with('success', "SIP account {$sipAccount->username} updated.");
    }

    public function reprovision(SipAccount $sipAccount)
    {
        abort_unless(in_array($sipAccount->user_id, auth()->user()->clientIds()), 403);

        if ($sipAccount->status === 'active') {
            $this->provisioning->provision($sipAccount);
            $message = "SIP account {$sipAccount->username} re-provisioned.";
        } else {
            $this->provisioning->deprovision($sipAccount);
            $message = "SIP account {$sipAccount->username} deprovisioned (status: {$sipAccount->status}).";
        }

        AuditService::logAction('reseller.sip_account.reprovisioned', $sipAccount);

        return back()->with('success', $message);
    }

    /**
     * AJAX: Check registration status for SIP usernames.
     */
    public function registrationStatus(Request $request)
    {
        $usernames = $request->input('usernames', []);
        if (empty($usernames) || !is_array($usernames)) {
            return response()->json([]);
        }

        $contacts = [];
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(3)
                ->post('http://127.0.0.1:8001/api/contacts/status', [
                    'usernames' => $usernames,
                ]);

            if ($response->successful()) {
                $contacts = $response->json();
            }
        } catch (\Throwable $e) {
            // Fallback to Asterisk CLI
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
}

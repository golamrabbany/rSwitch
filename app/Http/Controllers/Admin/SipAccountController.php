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

        if ($request->filled('user_id')) {
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

        return view('admin.sip-accounts.index', compact('sipAccounts'));
    }

    public function create(Request $request)
    {
        $users = User::whereIn('role', ['reseller', 'client'])
            ->active()
            ->orderBy('name')
            ->get();

        $selectedUserId = $request->query('user_id');

        return view('admin.sip-accounts.create', compact('users', 'selectedUserId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
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
        $users = User::whereIn('role', ['reseller', 'client'])
            ->active()
            ->orderBy('name')
            ->get();

        return view('admin.sip-accounts.edit', compact('sipAccount', 'users'));
    }

    public function update(Request $request, SipAccount $sipAccount)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
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
}

<?php

namespace App\Http\Controllers\Reseller;

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
        $descendantIds = auth()->user()->descendantIds();

        $query = SipAccount::whereIn('user_id', $descendantIds)->with('user');

        if ($request->filled('user_id') && in_array((int) $request->user_id, $descendantIds)) {
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

        $users = User::whereIn('id', $descendantIds)->orderBy('name')->get();

        return view('reseller.sip-accounts.index', compact('sipAccounts', 'users'));
    }

    public function create(Request $request)
    {
        $descendantIds = auth()->user()->descendantIds();
        $users = User::whereIn('id', $descendantIds)->active()->orderBy('name')->get();
        $selectedUserId = $request->query('user_id');

        return view('reseller.sip-accounts.create', compact('users', 'selectedUserId'));
    }

    public function store(Request $request)
    {
        $descendantIds = auth()->user()->descendantIds();

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', Rule::in($descendantIds)],
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

        $this->provisioning->provision($sip);

        AuditService::logCreated($sip, 'reseller.sip_account.created');

        return redirect()->route('reseller.sip-accounts.show', $sip)
            ->with('success', "SIP account {$sip->username} created and provisioned.");
    }

    public function show(SipAccount $sipAccount)
    {
        abort_unless(in_array($sipAccount->user_id, auth()->user()->descendantIds()), 403);

        $sipAccount->load('user');

        $provisioned = \DB::table('ps_endpoints')->where('id', $sipAccount->username)->exists();

        return view('reseller.sip-accounts.show', compact('sipAccount', 'provisioned'));
    }

    public function edit(SipAccount $sipAccount)
    {
        abort_unless(in_array($sipAccount->user_id, auth()->user()->descendantIds()), 403);

        $users = User::whereIn('id', auth()->user()->descendantIds())->active()->orderBy('name')->get();

        return view('reseller.sip-accounts.edit', compact('sipAccount', 'users'));
    }

    public function update(Request $request, SipAccount $sipAccount)
    {
        $descendantIds = auth()->user()->descendantIds();
        abort_unless(in_array($sipAccount->user_id, $descendantIds), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', Rule::in($descendantIds)],
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

        return redirect()->route('reseller.sip-accounts.show', $sipAccount)
            ->with('success', "SIP account {$sipAccount->username} updated.");
    }

    public function reprovision(SipAccount $sipAccount)
    {
        abort_unless(in_array($sipAccount->user_id, auth()->user()->descendantIds()), 403);

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
}

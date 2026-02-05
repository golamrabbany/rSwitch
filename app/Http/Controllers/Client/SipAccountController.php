<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SipAccount;
use App\Services\AuditService;
use App\Services\SipProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SipAccountController extends Controller
{
    public function __construct(
        private SipProvisioningService $provisioning,
    ) {}

    public function index(Request $request)
    {
        $query = SipAccount::where('user_id', auth()->id());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('caller_id_number', 'like', "%{$search}%");
            });
        }

        $sipAccounts = $query->orderBy('username')->paginate(20);

        return view('client.sip-accounts.index', compact('sipAccounts'));
    }

    public function show(SipAccount $sipAccount)
    {
        abort_unless($sipAccount->user_id === auth()->id(), 403);

        $provisioned = DB::table('ps_endpoints')
            ->where('id', $sipAccount->username)
            ->exists();

        return view('client.sip-accounts.show', compact('sipAccount', 'provisioned'));
    }

    public function edit(SipAccount $sipAccount)
    {
        abort_unless($sipAccount->user_id === auth()->id(), 403);

        return view('client.sip-accounts.edit', compact('sipAccount'));
    }

    public function update(Request $request, SipAccount $sipAccount)
    {
        abort_unless($sipAccount->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'password' => 'nullable|string|min:12',
            'caller_id_name' => 'required|string|max:80',
            'caller_id_number' => 'required|string|max:20',
        ]);

        $original = $sipAccount->getAttributes();

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $sipAccount->update($validated);

        AuditService::logUpdated($sipAccount, $original, 'client.sip_account.updated');

        if (isset($validated['password']) && $sipAccount->status === 'active') {
            $this->provisioning->provision($sipAccount);
        }

        return redirect()->route('client.sip-accounts.show', $sipAccount)
            ->with('success', 'SIP account updated.');
    }
}

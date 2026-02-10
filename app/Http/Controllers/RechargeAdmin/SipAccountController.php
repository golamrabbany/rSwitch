<?php

namespace App\Http\Controllers\RechargeAdmin;

use App\Http\Controllers\Controller;
use App\Models\SipAccount;
use App\Models\User;
use Illuminate\Http\Request;

class SipAccountController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $userIds = $currentUser->descendantIds();

        $query = SipAccount::whereIn('user_id', $userIds)
            ->with('user:id,name,role');

        // Search filter
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('caller_id_number', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Owner filter
        if ($userId = $request->query('user_id')) {
            if (in_array($userId, $userIds)) {
                $query->where('user_id', $userId);
            }
        }

        $sipAccounts = $query->orderBy('username')->paginate(25)->withQueryString();

        return view('recharge-admin.sip-accounts.index', compact('sipAccounts'));
    }

    public function show(SipAccount $sipAccount)
    {
        $currentUser = auth()->user();
        $userIds = $currentUser->descendantIds();

        // Authorization check
        abort_unless(in_array($sipAccount->user_id, $userIds), 403);

        $sipAccount->load('user:id,name,role,email');

        return view('recharge-admin.sip-accounts.show', compact('sipAccount'));
    }
}

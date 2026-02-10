<?php

namespace App\Http\Controllers\RechargeAdmin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $resellerIds = $currentUser->assignedResellers()->pluck('users.id')->toArray();
        $clientIds = User::whereIn('parent_id', $resellerIds)->pluck('id')->toArray();
        $allUserIds = array_merge($resellerIds, $clientIds);

        $query = User::whereIn('id', $allUserIds);

        // Search filter
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        // Status filter
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $users = $query->orderBy('name')->paginate(25)->withQueryString();

        return view('recharge-admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        // Authorization check
        abort_unless(auth()->user()->canRechargeBalance($user), 403);

        // Recent transactions for this user
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('recharge-admin.users.show', compact('user', 'recentTransactions'));
    }
}

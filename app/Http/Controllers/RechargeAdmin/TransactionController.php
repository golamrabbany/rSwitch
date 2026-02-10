<?php

namespace App\Http\Controllers\RechargeAdmin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $userIds = $currentUser->descendantIds();

        $query = Transaction::whereIn('user_id', $userIds)
            ->with(['user:id,name,role', 'creator:id,name']);

        // Date range filter
        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Type filter
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        // Owner filter
        if ($userId = $request->query('user_id')) {
            if (in_array($userId, $userIds)) {
                $query->where('user_id', $userId);
            }
        }

        // Created by filter (for tracking this recharge admin's transactions)
        if ($request->query('my_transactions')) {
            $query->where('created_by', $currentUser->id);
        }

        $transactions = $query->orderByDesc('created_at')->paginate(50)->withQueryString();

        // Get users for filter dropdown
        $users = User::whereIn('id', $userIds)
            ->whereIn('role', ['reseller', 'client'])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('recharge-admin.transactions.index', compact('transactions', 'users'));
    }

    public function show(Transaction $transaction)
    {
        $currentUser = auth()->user();
        $userIds = $currentUser->descendantIds();

        // Authorization check
        abort_unless(in_array($transaction->user_id, $userIds), 403);

        $transaction->load(['user:id,name,role,email', 'creator:id,name']);

        return view('recharge-admin.transactions.show', compact('transaction'));
    }
}

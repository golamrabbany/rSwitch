<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('user', 'creator');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('description', 'like', "%{$search}%");
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(30);

        $users = User::whereIn('role', ['reseller', 'client'])->orderBy('name')->get(['id', 'name', 'email', 'role']);

        // Calculate stats
        $stats = [
            'total_credits' => Transaction::where('amount', '>=', 0)->sum('amount'),
            'total_debits' => abs(Transaction::where('amount', '<', 0)->sum('amount')),
            'total_count' => Transaction::count(),
        ];

        return view('admin.transactions.index', compact('transactions', 'users', 'stats'));
    }

    public function show(Transaction $transaction)
    {
        $transaction->load('user', 'creator');

        return view('admin.transactions.show', compact('transaction'));
    }
}

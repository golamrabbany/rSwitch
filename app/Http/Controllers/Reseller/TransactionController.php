<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $descendantIds = auth()->user()->descendantIds();

        $query = Transaction::with('user')
            ->whereIn('user_id', $descendantIds);

        if ($request->filled('user_id') && in_array($request->user_id, $descendantIds)) {
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

        $transactions = $query->orderBy('created_at', 'desc')->paginate(30);

        $users = User::whereIn('id', $descendantIds)->orderBy('name')->get(['id', 'name', 'email']);

        $currentBalance = auth()->user()->balance;

        return view('reseller.transactions.index', compact('transactions', 'users', 'currentBalance'));
    }
}

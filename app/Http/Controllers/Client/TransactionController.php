<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::where('user_id', auth()->id());

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        $transactions = $query->with('creator:id,name')
            ->orderByDesc('created_at')
            ->paginate(30);

        $currentBalance = auth()->user()->balance;

        $statsQuery = Transaction::where('user_id', auth()->id());
        if ($request->filled('type')) $statsQuery->where('type', $request->type);
        if ($request->filled('date_from')) $statsQuery->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        if ($request->filled('date_to')) $statsQuery->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());

        $stats = $statsQuery->selectRaw('
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END), 0) as total_credit,
            COALESCE(SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END), 0) as total_debit
        ')->first();

        return view('client.transactions.index', compact('transactions', 'currentBalance', 'stats'));
    }
}

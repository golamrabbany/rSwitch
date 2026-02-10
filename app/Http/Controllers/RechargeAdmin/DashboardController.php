<?php

namespace App\Http\Controllers\RechargeAdmin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $resellerIds = $user->assignedResellers()->pluck('users.id')->toArray();
        $clientIds = User::whereIn('parent_id', $resellerIds)->pluck('id')->toArray();
        $allUserIds = array_merge($resellerIds, $clientIds);

        // Recent transactions performed by this recharge admin
        $recentTransactions = Transaction::whereIn('user_id', $allUserIds)
            ->where('created_by', $user->id)
            ->with('user:id,name,role')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Today's stats
        $todayStats = Transaction::whereIn('user_id', $allUserIds)
            ->where('created_by', $user->id)
            ->whereDate('created_at', today())
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(ABS(amount)), 0) as total')
            ->first();

        return view('recharge-admin.dashboard', [
            'assignedResellers' => count($resellerIds),
            'totalClients' => count($clientIds),
            'recentTransactions' => $recentTransactions,
            'todayTransactionCount' => $todayStats->count ?? 0,
            'todayTransactionTotal' => $todayStats->total ?? 0,
        ]);
    }
}

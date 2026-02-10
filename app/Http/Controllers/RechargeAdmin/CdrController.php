<?php

namespace App\Http\Controllers\RechargeAdmin;

use App\Http\Controllers\Controller;
use App\Models\CallRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CdrController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $userIds = $currentUser->descendantIds();

        // Date range (default: today, always required for partition pruning)
        $dateFrom = $request->query('date_from', Carbon::today()->toDateString());
        $dateTo = $request->query('date_to', Carbon::today()->toDateString());

        $query = CallRecord::query()
            ->whereBetween('call_start', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ])
            ->whereIn('user_id', $userIds)
            ->with(['user:id,name', 'sipAccount:id,username']);

        // Search filter (prefix match only for index usage)
        if ($caller = $request->query('caller')) {
            $query->where('caller', 'like', "{$caller}%");
        }

        if ($callee = $request->query('callee')) {
            $query->where('callee', 'like', "{$callee}%");
        }

        // Disposition filter
        if ($disposition = $request->query('disposition')) {
            $query->where('disposition', $disposition);
        }

        // Owner filter
        if ($userId = $request->query('user_id')) {
            if (in_array($userId, $userIds)) {
                $query->where('user_id', $userId);
            }
        }

        $calls = $query->orderByDesc('call_start')->paginate(50)->withQueryString();

        // Stats from cdr_summary_daily
        $stats = DB::table('cdr_summary_daily')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->whereIn('user_id', $userIds)
            ->selectRaw('
                COALESCE(SUM(total_calls), 0) as total_calls,
                COALESCE(SUM(answered_calls), 0) as answered_calls,
                COALESCE(SUM(total_duration), 0) as total_duration,
                COALESCE(SUM(total_cost), 0) as total_cost
            ')
            ->first();

        // Get users for filter dropdown
        $users = User::whereIn('id', $userIds)
            ->whereIn('role', ['reseller', 'client'])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('recharge-admin.cdr.index', compact('calls', 'stats', 'users', 'dateFrom', 'dateTo'));
    }

    public function show(Request $request, string $uuid)
    {
        $currentUser = auth()->user();
        $userIds = $currentUser->descendantIds();

        // Date param required for partition pruning
        $date = $request->query('date', Carbon::today()->toDateString());

        $call = CallRecord::where('uuid', $uuid)
            ->whereBetween('call_start', [
                Carbon::parse($date)->startOfDay(),
                Carbon::parse($date)->endOfDay(),
            ])
            ->whereIn('user_id', $userIds)
            ->with(['user:id,name,role', 'sipAccount:id,username'])
            ->firstOrFail();

        return view('recharge-admin.cdr.show', compact('call'));
    }
}

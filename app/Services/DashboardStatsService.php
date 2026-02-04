<?php

namespace App\Services;

use App\Models\CallRecord;
use App\Models\Did;
use App\Models\SipAccount;
use App\Models\Trunk;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    /**
     * Get call stats from cdr_summary_daily for the last N days.
     */
    public function getCallStats(?array $userIds, int $days = 7): array
    {
        $dateFrom = Carbon::today()->subDays($days - 1)->toDateString();
        $dateTo = Carbon::today()->toDateString();

        $query = DB::table('cdr_summary_daily')
            ->whereBetween('date', [$dateFrom, $dateTo]);

        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        $row = $query->selectRaw('
            COALESCE(SUM(total_calls), 0) as total_calls,
            COALESCE(SUM(answered_calls), 0) as answered_calls,
            COALESCE(SUM(total_duration), 0) as total_duration,
            COALESCE(SUM(total_billable), 0) as total_billable,
            COALESCE(SUM(total_cost), 0) as total_cost
        ')->first();

        $totalCalls = (int) $row->total_calls;
        $answeredCalls = (int) $row->answered_calls;

        return [
            'total_calls' => $totalCalls,
            'answered_calls' => $answeredCalls,
            'asr' => $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 1) : 0,
            'total_duration' => (int) $row->total_duration,
            'total_billable' => (int) $row->total_billable,
            'total_cost' => (float) $row->total_cost,
        ];
    }

    /**
     * Get today's call stats from cdr_summary_daily.
     */
    public function getTodayCallStats(?array $userIds): array
    {
        $today = Carbon::today()->toDateString();

        $query = DB::table('cdr_summary_daily')
            ->where('date', $today);

        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        $row = $query->selectRaw('
            COALESCE(SUM(total_calls), 0) as today_calls,
            COALESCE(SUM(answered_calls), 0) as today_answered,
            COALESCE(SUM(total_cost), 0) as today_cost
        ')->first();

        return [
            'today_calls' => (int) $row->today_calls,
            'today_answered' => (int) $row->today_answered,
            'today_cost' => (float) $row->today_cost,
        ];
    }

    /**
     * Get recent calls with partition pruning (today only).
     */
    public function getRecentCalls(?array $userIds, int $limit = 10): Collection
    {
        $query = CallRecord::query()
            ->whereBetween('call_start', [
                Carbon::today()->startOfDay(),
                Carbon::today()->endOfDay(),
            ]);

        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        return $query->with(['user:id,name', 'sipAccount:id,username'])
            ->orderByDesc('call_start')
            ->limit($limit)
            ->get();
    }

    /**
     * Get entity counts scoped by user role.
     */
    public function getEntityCounts(User $user): array
    {
        if ($user->isAdmin()) {
            return [
                'resellers' => User::where('role', 'reseller')->count(),
                'clients' => User::where('role', 'client')->count(),
                'sip_accounts' => SipAccount::count(),
                'active_trunks' => Trunk::where('status', 'active')->count(),
                'active_dids' => Did::where('status', 'active')->count(),
                'pending_kyc' => User::where('kyc_status', 'pending')->count(),
            ];
        }

        if ($user->isReseller()) {
            $clientIds = User::where('parent_id', $user->id)->pluck('id');

            return [
                'clients' => $clientIds->count(),
                'sip_accounts' => SipAccount::where('user_id', $user->id)
                    ->orWhereIn('user_id', $clientIds)
                    ->count(),
                'dids' => Did::where('assigned_to_user_id', $user->id)
                    ->orWhereIn('assigned_to_user_id', $clientIds)
                    ->count(),
            ];
        }

        // Client
        return [
            'sip_accounts' => SipAccount::where('user_id', $user->id)->count(),
            'dids' => Did::where('assigned_to_user_id', $user->id)->count(),
        ];
    }
}

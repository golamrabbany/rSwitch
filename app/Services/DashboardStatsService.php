<?php

namespace App\Services;

use App\Models\CallRecord;
use App\Models\Did;
use App\Models\Invoice;
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
        $failedCalls = $totalCalls - $answeredCalls;

        return [
            'total_calls' => $totalCalls,
            'answered_calls' => $answeredCalls,
            'failed_calls' => $failedCalls,
            'asr' => $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 1) : 0,
            'acd' => $answeredCalls > 0 ? round((int) $row->total_duration / $answeredCalls) : 0,
            'total_duration' => (int) $row->total_duration,
            'total_billable' => (int) $row->total_billable,
            'total_cost' => (float) $row->total_cost,
        ];
    }

    /**
     * Get previous period stats for trend comparison.
     */
    public function getPreviousPeriodStats(?array $userIds, int $days = 7): array
    {
        $dateFrom = Carbon::today()->subDays(($days * 2) - 1)->toDateString();
        $dateTo = Carbon::today()->subDays($days)->toDateString();

        $query = DB::table('cdr_summary_daily')
            ->whereBetween('date', [$dateFrom, $dateTo]);

        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        $row = $query->selectRaw('
            COALESCE(SUM(total_calls), 0) as total_calls,
            COALESCE(SUM(answered_calls), 0) as answered_calls,
            COALESCE(SUM(total_cost), 0) as total_cost
        ')->first();

        return [
            'total_calls' => (int) $row->total_calls,
            'answered_calls' => (int) $row->answered_calls,
            'total_cost' => (float) $row->total_cost,
        ];
    }

    /**
     * Get daily call data for the last N days (for charts).
     */
    public function getDailyCallData(?array $userIds, int $days = 7): array
    {
        $dateFrom = Carbon::today()->subDays($days - 1)->toDateString();
        $dateTo = Carbon::today()->toDateString();

        $query = DB::table('cdr_summary_daily')
            ->whereBetween('date', [$dateFrom, $dateTo]);

        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        $data = $query->selectRaw('
            date,
            COALESCE(SUM(total_calls), 0) as calls,
            COALESCE(SUM(answered_calls), 0) as answered,
            COALESCE(SUM(total_cost), 0) as revenue
        ')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $dayData = $data->get($date);
            $result[] = [
                'date' => $date,
                'label' => Carbon::parse($date)->format('D'),
                'calls' => $dayData ? (int) $dayData->calls : 0,
                'answered' => $dayData ? (int) $dayData->answered : 0,
                'revenue' => $dayData ? (float) $dayData->revenue : 0,
            ];
        }

        return $result;
    }

    /**
     * Get hourly call data for today (for charts).
     */
    public function getHourlyCallData(?array $userIds): array
    {
        $today = Carbon::today()->toDateString();

        $query = DB::table('cdr_summary_hourly')
            ->where('hour_start', '>=', Carbon::today()->startOfDay())
            ->where('hour_start', '<', Carbon::today()->endOfDay());

        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        $data = $query->selectRaw('
            HOUR(hour_start) as hour,
            COALESCE(SUM(total_calls), 0) as calls,
            COALESCE(SUM(answered_calls), 0) as answered
        ')
            ->groupByRaw('HOUR(hour_start)')
            ->orderByRaw('HOUR(hour_start)')
            ->get()
            ->keyBy('hour');

        $result = [];
        $currentHour = Carbon::now()->hour;
        for ($h = 0; $h <= $currentHour; $h++) {
            $hourData = $data->get($h);
            $result[] = [
                'hour' => $h,
                'label' => sprintf('%02d:00', $h),
                'calls' => $hourData ? (int) $hourData->calls : 0,
                'answered' => $hourData ? (int) $hourData->answered : 0,
            ];
        }

        return $result;
    }

    /**
     * Get top destinations by call count.
     */
    public function getTopDestinations(?array $userIds, int $limit = 5): Collection
    {
        $dateFrom = Carbon::today()->subDays(6)->toDateString();

        $query = DB::table('cdr_summary_destination')
            ->where('date', '>=', $dateFrom);

        return $query->selectRaw('
            matched_prefix as destination_prefix,
            SUM(total_calls) as calls,
            SUM(answered_calls) as answered,
            SUM(total_cost) as revenue,
            SUM(total_duration) as duration
        ')
            ->groupBy('matched_prefix')
            ->orderByRaw('SUM(total_calls) DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get active calls count (currently in progress).
     */
    public function getActiveCallsCount(?array $userIds = null): int
    {
        $query = CallRecord::where('status', 'in_progress');

        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        return $query->count();
    }

    /**
     * Get system health metrics for admin dashboard.
     */
    public function getSystemHealth(): array
    {
        $activeTrunks = Trunk::where('status', 'active')->count();
        $totalTrunks = Trunk::count();
        $unhealthyTrunks = Trunk::where('status', 'active')
            ->where('health_check', true)
            ->whereIn('health_status', ['down', 'degraded'])
            ->count();

        $activeSipAccounts = SipAccount::where('status', 'active')->count();
        $totalSipAccounts = SipAccount::count();

        $pendingInvoices = Invoice::whereIn('status', ['draft', 'issued'])->count();
        $overdueInvoices = Invoice::where('status', 'issued')
            ->where('due_date', '<', Carbon::today())
            ->count();

        return [
            'trunks' => [
                'active' => $activeTrunks,
                'total' => $totalTrunks,
                'unhealthy' => $unhealthyTrunks,
                'health_pct' => $activeTrunks > 0 ? round((($activeTrunks - $unhealthyTrunks) / $activeTrunks) * 100) : 100,
            ],
            'sip_accounts' => [
                'active' => $activeSipAccounts,
                'total' => $totalSipAccounts,
            ],
            'invoices' => [
                'pending' => $pendingInvoices,
                'overdue' => $overdueInvoices,
            ],
        ];
    }

    /**
     * Get financial summary for admin dashboard.
     */
    public function getFinancialSummary(?array $userIds = null): array
    {
        $thisMonth = Carbon::now()->startOfMonth()->toDateString();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth()->toDateString();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth()->toDateString();

        // This month's revenue
        $thisMonthQuery = DB::table('cdr_summary_daily')
            ->where('date', '>=', $thisMonth);
        if ($userIds !== null) {
            $thisMonthQuery->whereIn('user_id', $userIds);
        }
        $thisMonthRevenue = $thisMonthQuery->sum('total_cost');

        // Last month's revenue
        $lastMonthQuery = DB::table('cdr_summary_daily')
            ->whereBetween('date', [$lastMonth, $lastMonthEnd]);
        if ($userIds !== null) {
            $lastMonthQuery->whereIn('user_id', $userIds);
        }
        $lastMonthRevenue = $lastMonthQuery->sum('total_cost');

        // Total outstanding balance (unpaid invoices)
        $invoiceQuery = Invoice::whereIn('status', ['issued']);
        if ($userIds !== null) {
            $invoiceQuery->whereIn('user_id', $userIds);
        }
        $outstandingBalance = $invoiceQuery->sum('total_amount');

        // Total user balances
        $balanceQuery = User::whereIn('role', ['reseller', 'client'])
            ->where('balance', '>', 0);
        if ($userIds !== null) {
            $balanceQuery->whereIn('id', $userIds);
        }
        $totalUserBalance = $balanceQuery->sum('balance');

        return [
            'this_month_revenue' => (float) $thisMonthRevenue,
            'last_month_revenue' => (float) $lastMonthRevenue,
            'revenue_change' => $lastMonthRevenue > 0
                ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
                : 0,
            'outstanding_balance' => (float) $outstandingBalance,
            'total_user_balance' => (float) $totalUserBalance,
        ];
    }

    /**
     * Get today's call stats from cdr_summary_daily.
     */
    public function getTodayCallStats(?array $userIds): array
    {
        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        $query = DB::table('cdr_summary_daily')
            ->where('date', $today);

        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        $row = $query->selectRaw('
            COALESCE(SUM(total_calls), 0) as today_calls,
            COALESCE(SUM(answered_calls), 0) as today_answered,
            COALESCE(SUM(total_duration), 0) as today_duration,
            COALESCE(SUM(total_cost), 0) as today_cost
        ')->first();

        // Get yesterday's stats for comparison
        $yesterdayQuery = DB::table('cdr_summary_daily')
            ->where('date', $yesterday);

        if ($userIds !== null) {
            $yesterdayQuery->whereIn('user_id', $userIds);
        }

        $yesterdayRow = $yesterdayQuery->selectRaw('
            COALESCE(SUM(total_calls), 0) as calls,
            COALESCE(SUM(total_cost), 0) as cost
        ')->first();

        $todayCalls = (int) $row->today_calls;
        $yesterdayCalls = (int) $yesterdayRow->calls;
        $todayCost = (float) $row->today_cost;
        $yesterdayCost = (float) $yesterdayRow->cost;

        return [
            'today_calls' => $todayCalls,
            'today_answered' => (int) $row->today_answered,
            'today_duration' => (int) $row->today_duration,
            'today_cost' => $todayCost,
            'yesterday_calls' => $yesterdayCalls,
            'yesterday_cost' => $yesterdayCost,
            'calls_change' => $yesterdayCalls > 0
                ? round((($todayCalls - $yesterdayCalls) / $yesterdayCalls) * 100, 1)
                : ($todayCalls > 0 ? 100 : 0),
            'cost_change' => $yesterdayCost > 0
                ? round((($todayCost - $yesterdayCost) / $yesterdayCost) * 100, 1)
                : ($todayCost > 0 ? 100 : 0),
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
        // Super Admin sees global counts
        if ($user->isSuperAdmin()) {
            return [
                'resellers' => User::where('role', 'reseller')->count(),
                'clients' => User::where('role', 'client')->count(),
                'sip_accounts' => SipAccount::count(),
                'active_trunks' => Trunk::where('status', 'active')->count(),
                'active_dids' => Did::where('status', 'active')->count(),
                'pending_kyc' => User::where('kyc_status', 'pending')->count(),
            ];
        }

        // Regular Admin sees scoped counts (assigned resellers and their clients)
        if ($user->isRegularAdmin()) {
            $resellerIds = $user->managedResellerIds();
            $clientIds = User::whereIn('parent_id', $resellerIds)->pluck('id')->toArray();
            $allUserIds = array_merge($resellerIds, $clientIds);

            return [
                'resellers' => count($resellerIds),
                'clients' => count($clientIds),
                'sip_accounts' => SipAccount::whereIn('user_id', $allUserIds)->count(),
                'active_trunks' => null, // Regular admins don't manage trunks
                'active_dids' => Did::whereIn('assigned_to_user_id', $allUserIds)->where('status', 'active')->count(),
                'pending_kyc' => User::whereIn('id', $allUserIds)->where('kyc_status', 'pending')->count(),
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

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardStatsService;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $service = new DashboardStatsService();

        // Super admin sees global stats, regular admin sees scoped stats
        $scopeIds = $user->isSuperAdmin() ? null : $user->descendantIds();

        $entityCounts = $service->getEntityCounts($user);
        $weekStats = $service->getCallStats($scopeIds, 7);
        $prevWeekStats = $service->getPreviousPeriodStats($scopeIds, 7);
        $todayStats = $service->getTodayCallStats($scopeIds);
        $recentCalls = $service->getRecentCalls($scopeIds, 10);
        $dailyData = $service->getDailyCallData($scopeIds, 7);
        $hourlyData = $service->getHourlyCallData($scopeIds);
        $topDestinations = $service->getTopDestinations($scopeIds, 5);
        $activeCalls = $service->getActiveCallsCount($scopeIds);
        $systemHealth = $service->getSystemHealth();
        $financialSummary = $service->getFinancialSummary($scopeIds);

        return view('admin.dashboard', compact(
            'entityCounts',
            'weekStats',
            'prevWeekStats',
            'todayStats',
            'recentCalls',
            'dailyData',
            'hourlyData',
            'topDestinations',
            'activeCalls',
            'systemHealth',
            'financialSummary'
        ));
    }
}

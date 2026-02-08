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

        $entityCounts = $service->getEntityCounts($user);
        $weekStats = $service->getCallStats(null, 7);
        $prevWeekStats = $service->getPreviousPeriodStats(null, 7);
        $todayStats = $service->getTodayCallStats(null);
        $recentCalls = $service->getRecentCalls(null, 10);
        $dailyData = $service->getDailyCallData(null, 7);
        $hourlyData = $service->getHourlyCallData(null);
        $topDestinations = $service->getTopDestinations(null, 5);
        $activeCalls = $service->getActiveCallsCount(null);
        $systemHealth = $service->getSystemHealth();
        $financialSummary = $service->getFinancialSummary();

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

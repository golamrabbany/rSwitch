<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Services\DashboardStatsService;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $childIds = $user->descendantIds();
        $service = new DashboardStatsService();

        $entityCounts = $service->getEntityCounts($user);
        $weekStats = $service->getCallStats($childIds, 7);
        $prevWeekStats = $service->getPreviousPeriodStats($childIds, 7);
        $todayStats = $service->getTodayCallStats($childIds);
        $dailyData = $service->getDailyCallData($childIds, 7);
        $recentCalls = $service->getRecentCalls($childIds, 10);

        $broadcastQuery = Broadcast::whereIn('user_id', $childIds);
        $broadcastStats = [
            'running' => (clone $broadcastQuery)->where('status', 'running')->count(),
            'completed' => (clone $broadcastQuery)->where('status', 'completed')->count(),
            'total' => (clone $broadcastQuery)->count(),
        ];

        return view('reseller.dashboard', compact(
            'entityCounts', 'weekStats', 'prevWeekStats', 'todayStats', 'dailyData', 'recentCalls', 'broadcastStats'
        ));
    }
}

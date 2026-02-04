<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\DashboardStatsService;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $service = new DashboardStatsService();

        $entityCounts = $service->getEntityCounts($user);
        $weekStats = $service->getCallStats([$user->id], 7);
        $todayStats = $service->getTodayCallStats([$user->id]);
        $recentCalls = $service->getRecentCalls([$user->id], 10);

        return view('client.dashboard', compact(
            'entityCounts', 'weekStats', 'todayStats', 'recentCalls'
        ));
    }
}

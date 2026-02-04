<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
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
        $todayStats = $service->getTodayCallStats($childIds);
        $recentCalls = $service->getRecentCalls($childIds, 10);

        return view('reseller.dashboard', compact(
            'entityCounts', 'weekStats', 'todayStats', 'recentCalls'
        ));
    }
}

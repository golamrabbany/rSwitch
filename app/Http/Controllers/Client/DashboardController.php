<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
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
        $dailyData = $service->getDailyCallData([$user->id], 7);
        $recentCalls = $service->getRecentCalls([$user->id], 10);

        $broadcastStats = [
            'running' => Broadcast::where('user_id', $user->id)->where('status', 'running')->count(),
            'completed' => Broadcast::where('user_id', $user->id)->where('status', 'completed')->count(),
            'total' => Broadcast::where('user_id', $user->id)->count(),
        ];

        return view('client.dashboard', compact(
            'entityCounts', 'weekStats', 'todayStats', 'dailyData', 'recentCalls', 'broadcastStats'
        ));
    }
}

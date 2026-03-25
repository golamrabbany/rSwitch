<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallRecord;
use App\Models\Trunk;
use Illuminate\Support\Facades\Artisan;

class TrunkMonitorController extends Controller
{
    public function index()
    {
        $trunks = Trunk::orderBy('name')->get();

        // Active calls per trunk (single query)
        $activeInbound = CallRecord::where('status', 'in_progress')
            ->whereNotNull('incoming_trunk_id')
            ->selectRaw('incoming_trunk_id as trunk_id, COUNT(*) as cnt')
            ->groupBy('incoming_trunk_id')
            ->pluck('cnt', 'trunk_id');

        $activeOutbound = CallRecord::where('status', 'in_progress')
            ->whereNotNull('outgoing_trunk_id')
            ->selectRaw('outgoing_trunk_id as trunk_id, COUNT(*) as cnt')
            ->groupBy('outgoing_trunk_id')
            ->pluck('cnt', 'trunk_id');

        // Summary stats
        $totalTrunks = $trunks->count();
        $activeTrunks = $trunks->where('status', 'active')->count();
        $downTrunks = $trunks->where('health_status', 'down')->count();
        $totalActiveCalls = $activeInbound->sum() + $activeOutbound->sum();

        return view('admin.trunk-monitor.index', compact(
            'trunks', 'activeInbound', 'activeOutbound',
            'totalTrunks', 'activeTrunks', 'downTrunks', 'totalActiveCalls'
        ));
    }

    public function refresh()
    {
        Artisan::call('trunk:health-check');

        return redirect()->route('admin.trunk-monitor.index')
            ->with('success', 'Health check completed');
    }
}

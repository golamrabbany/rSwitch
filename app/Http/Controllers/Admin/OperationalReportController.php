<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallRecord;
use App\Models\SipAccount;
use App\Models\Trunk;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationalReportController extends Controller
{
    /**
     * Operational Reports Dashboard
     */
    public function index()
    {
        $today = now()->startOfDay();

        // Active calls — single query with conditional counts
        $activeStats = CallRecord::where('status', 'in_progress')
            ->selectRaw('
                COUNT(*) as total,
                SUM(call_flow = "trunk_to_sip") as inbound,
                SUM(call_flow = "sip_to_trunk") as outbound
            ')->first();

        $activeCalls = (int) $activeStats->total;
        $inboundActive = (int) $activeStats->inbound;
        $outboundActive = (int) $activeStats->outbound;

        // Today's stats — single query with conditional aggregations
        $todayStats = CallRecord::where('call_start', '>=', $today)
            ->selectRaw('
                SUM(call_flow = "trunk_to_sip") as inbound,
                SUM(call_flow = "sip_to_trunk") as outbound,
                SUM(disposition = "ANSWERED") as answered,
                COALESCE(SUM(CASE WHEN disposition = "ANSWERED" THEN billsec ELSE 0 END), 0) as duration
            ')->first();

        $todayInbound = (int) $todayStats->inbound;
        $todayOutbound = (int) $todayStats->outbound;
        $todayTotal = $todayInbound + $todayOutbound;
        $todayAnswered = (int) $todayStats->answered;
        $todayAsr = $todayTotal > 0 ? round(($todayAnswered / $todayTotal) * 100, 1) : 0;
        $todayMinutes = round((int) $todayStats->duration / 60, 1);

        // Recent active calls (limit 10)
        $recentActive = CallRecord::with(['user', 'sipAccount', 'incomingTrunk', 'outgoingTrunk'])
            ->where('status', 'in_progress')
            ->orderBy('call_start', 'desc')
            ->limit(10)
            ->get();

        // Top 5 active SIP accounts
        $topSipAccounts = CallRecord::where('call_start', '>=', $today)
            ->whereNotNull('sip_account_id')
            ->selectRaw('sip_account_id, COUNT(*) as call_count')
            ->groupBy('sip_account_id')
            ->orderByDesc('call_count')
            ->limit(5)
            ->with('sipAccount')
            ->get();

        // Top 5 active trunks
        $topTrunks = CallRecord::where('call_start', '>=', $today)
            ->selectRaw('
                COALESCE(outgoing_trunk_id, incoming_trunk_id) as trunk_id,
                COUNT(*) as call_count
            ')
            ->groupBy('trunk_id')
            ->orderByDesc('call_count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $item->trunk = Trunk::find($item->trunk_id);
                return $item;
            })
            ->filter(fn($item) => $item->trunk !== null);

        return view('admin.operational-reports.index', compact(
            'activeCalls',
            'inboundActive',
            'outboundActive',
            'todayInbound',
            'todayOutbound',
            'todayTotal',
            'todayAnswered',
            'todayAsr',
            'todayMinutes',
            'recentActive',
            'topSipAccounts',
            'topTrunks'
        ));
    }

    /**
     * Active Calls List
     */
    public function activeCalls(Request $request)
    {
        $query = CallRecord::with(['user', 'sipAccount', 'incomingTrunk', 'outgoingTrunk', 'did'])
            ->where('status', 'in_progress');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller', 'like', $search . '%')
                  ->orWhere('callee', 'like', $search . '%');
            });
        }

        // Filter by call flow
        if ($request->filled('call_flow')) {
            $query->where('call_flow', $request->call_flow);
        }

        // Filter by call state
        if ($request->filled('call_state')) {
            if ($request->call_state === 'answered') {
                $query->where('disposition', 'ANSWERED');
            } elseif ($request->call_state === 'ringing') {
                $query->whereNull('disposition');
            }
        }

        // Paginate with 100 per page for large lists
        $calls = $query->orderBy('call_start', 'desc')->paginate(100);

        // Add call_state attribute to each call
        $calls->getCollection()->transform(function ($call) {
            // Determine call state based on disposition and duration
            if ($call->disposition === 'ANSWERED') {
                $call->call_state = 'answered';
            } elseif ($call->billsec > 0) {
                $call->call_state = 'answered';
            } else {
                // Check how long the call has been ringing
                $ringTime = now()->diffInSeconds($call->call_start);
                if ($ringTime < 5) {
                    $call->call_state = 'processing';
                } else {
                    $call->call_state = 'ringing';
                }
            }
            return $call;
        });

        // Stats
        $totalActive = CallRecord::where('status', 'in_progress')->count();
        $inboundActive = CallRecord::where('status', 'in_progress')
            ->where('call_flow', 'trunk_to_sip')->count();
        $outboundActive = CallRecord::where('status', 'in_progress')
            ->where('call_flow', 'sip_to_trunk')->count();

        // Call state stats
        $answeredCount = CallRecord::where('status', 'in_progress')
            ->where('disposition', 'ANSWERED')->count();
        $ringingCount = CallRecord::where('status', 'in_progress')
            ->whereNull('disposition')->count();

        return view('admin.operational-reports.active-calls', compact(
            'calls',
            'totalActive',
            'inboundActive',
            'outboundActive',
            'answeredCount',
            'ringingCount'
        ));
    }

    /**
     * Inbound Calls List
     */
    public function inboundCalls(Request $request)
    {
        $query = CallRecord::with(['user', 'sipAccount', 'incomingTrunk', 'did'])
            ->where('call_flow', 'trunk_to_sip');

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('call_start', '>=', $request->date_from);
        } else {
            // Default to today
            $query->where('call_start', '>=', now()->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('call_start', '<=', $request->date_to . ' 23:59:59');
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller', 'like', $search . '%')
                  ->orWhere('callee', 'like', $search . '%');
            });
        }

        // Disposition filter
        if ($request->filled('disposition')) {
            $query->where('disposition', $request->disposition);
        }

        // Trunk filter
        if ($request->filled('trunk_id')) {
            $query->where('incoming_trunk_id', $request->trunk_id);
        }

        $calls = $query->orderBy('call_start', 'desc')->paginate(50);

        // Stats for the filtered period
        $statsQuery = CallRecord::where('call_flow', 'trunk_to_sip');
        if ($request->filled('date_from')) {
            $statsQuery->where('call_start', '>=', $request->date_from);
        } else {
            $statsQuery->where('call_start', '>=', now()->startOfDay());
        }
        if ($request->filled('date_to')) {
            $statsQuery->where('call_start', '<=', $request->date_to . ' 23:59:59');
        }

        $totalCalls = (clone $statsQuery)->count();
        $answeredCalls = (clone $statsQuery)->where('disposition', 'ANSWERED')->count();
        $asr = $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 1) : 0;
        $totalMinutes = round((clone $statsQuery)->where('disposition', 'ANSWERED')->sum('billsec') / 60, 1);

        $trunks = Trunk::whereIn('direction', ['incoming', 'both'])->orderBy('name')->get();

        return view('admin.operational-reports.inbound', compact(
            'calls',
            'totalCalls',
            'answeredCalls',
            'asr',
            'totalMinutes',
            'trunks'
        ));
    }

    /**
     * Outbound Calls List
     */
    public function outboundCalls(Request $request)
    {
        $query = CallRecord::with(['user', 'sipAccount', 'outgoingTrunk'])
            ->where('call_flow', 'sip_to_trunk');

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('call_start', '>=', $request->date_from);
        } else {
            // Default to today
            $query->where('call_start', '>=', now()->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('call_start', '<=', $request->date_to . ' 23:59:59');
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller', 'like', $search . '%')
                  ->orWhere('callee', 'like', $search . '%');
            });
        }

        // Disposition filter
        if ($request->filled('disposition')) {
            $query->where('disposition', $request->disposition);
        }

        // Trunk filter
        if ($request->filled('trunk_id')) {
            $query->where('outgoing_trunk_id', $request->trunk_id);
        }

        // User filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $calls = $query->orderBy('call_start', 'desc')->paginate(50);

        // Stats for the filtered period
        $statsQuery = CallRecord::where('call_flow', 'sip_to_trunk');
        if ($request->filled('date_from')) {
            $statsQuery->where('call_start', '>=', $request->date_from);
        } else {
            $statsQuery->where('call_start', '>=', now()->startOfDay());
        }
        if ($request->filled('date_to')) {
            $statsQuery->where('call_start', '<=', $request->date_to . ' 23:59:59');
        }

        $totalCalls = (clone $statsQuery)->count();
        $answeredCalls = (clone $statsQuery)->where('disposition', 'ANSWERED')->count();
        $asr = $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 1) : 0;
        $totalMinutes = round((clone $statsQuery)->where('disposition', 'ANSWERED')->sum('billsec') / 60, 1);
        $totalCost = (clone $statsQuery)->where('status', 'rated')->sum('total_cost');

        $trunks = Trunk::whereIn('direction', ['outgoing', 'both'])->orderBy('name')->get();

        return view('admin.operational-reports.outbound', compact(
            'calls',
            'totalCalls',
            'answeredCalls',
            'asr',
            'totalMinutes',
            'totalCost',
            'trunks'
        ));
    }

    /**
     * P2P Calls List (SIP-to-SIP internal calls)
     */
    public function p2pCalls(Request $request)
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::today()->startOfDay();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::today()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            $dateFrom = $dateTo->copy()->startOfDay();
        }

        if ($dateFrom->diffInDays($dateTo) > 31) {
            $dateTo = $dateFrom->copy()->addDays(31)->endOfDay();
        }

        // Build query — call_start first for partition pruning
        $query = CallRecord::query()
            ->where('call_flow', 'sip_to_sip')
            ->whereBetween('call_start', [$dateFrom, $dateTo]);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('disposition')) {
            $query->where('disposition', $request->disposition);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller', 'like', "{$search}%")
                  ->orWhere('callee', 'like', "{$search}%");
            });
        }

        $records = $query->with(['user:id,name', 'sipAccount:id,username'])
            ->orderByDesc('call_start')
            ->paginate(50);

        // Stats
        $statsQuery = CallRecord::query()
            ->where('call_flow', 'sip_to_sip')
            ->whereBetween('call_start', [$dateFrom, $dateTo]);

        if ($request->filled('user_id')) {
            $statsQuery->where('user_id', $request->user_id);
        }
        if ($request->filled('disposition')) {
            $statsQuery->where('disposition', $request->disposition);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $statsQuery->where(function ($q) use ($search) {
                $q->where('caller', 'like', "{$search}%")
                  ->orWhere('callee', 'like', "{$search}%");
            });
        }

        $statsRow = $statsQuery->selectRaw('
            COUNT(*) as total_calls,
            SUM(disposition = "ANSWERED") as answered_calls,
            COALESCE(SUM(duration), 0) as total_duration,
            COALESCE(SUM(billsec), 0) as total_billsec
        ')->first();

        $stats = [
            'total_calls' => $statsRow->total_calls ?? 0,
            'answered_calls' => $statsRow->answered_calls ?? 0,
            'total_duration' => $statsRow->total_duration ?? 0,
            'total_billsec' => $statsRow->total_billsec ?? 0,
        ];

        $users = User::select('id', 'name', 'role')->orderBy('name')->get();

        return view('admin.operational-reports.p2p', compact(
            'records', 'stats', 'users', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * Summary Calls - Combined Inbound & Outbound
     */
    public function summaryCalls(Request $request)
    {
        // Date range filter
        $dateFrom = $request->filled('date_from') ? $request->date_from : now()->startOfDay()->format('Y-m-d');
        $dateTo = $request->filled('date_to') ? $request->date_to : now()->format('Y-m-d');

        // Base query for stats
        $baseQuery = CallRecord::where('call_start', '>=', $dateFrom)
            ->where('call_start', '<=', $dateTo . ' 23:59:59');

        // Overall Stats
        $totalCalls = (clone $baseQuery)->count();
        $answeredCalls = (clone $baseQuery)->where('disposition', 'ANSWERED')->count();
        $asr = $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 1) : 0;
        $totalMinutes = round((clone $baseQuery)->where('disposition', 'ANSWERED')->sum('billsec') / 60, 1);
        $totalCost = (clone $baseQuery)->where('status', 'rated')->sum('total_cost');

        // Inbound Stats
        $inboundTotal = (clone $baseQuery)->where('call_flow', 'trunk_to_sip')->count();
        $inboundAnswered = (clone $baseQuery)->where('call_flow', 'trunk_to_sip')->where('disposition', 'ANSWERED')->count();
        $inboundAsr = $inboundTotal > 0 ? round(($inboundAnswered / $inboundTotal) * 100, 1) : 0;
        $inboundMinutes = round((clone $baseQuery)->where('call_flow', 'trunk_to_sip')->where('disposition', 'ANSWERED')->sum('billsec') / 60, 1);

        // Outbound Stats
        $outboundTotal = (clone $baseQuery)->where('call_flow', 'sip_to_trunk')->count();
        $outboundAnswered = (clone $baseQuery)->where('call_flow', 'sip_to_trunk')->where('disposition', 'ANSWERED')->count();
        $outboundAsr = $outboundTotal > 0 ? round(($outboundAnswered / $outboundTotal) * 100, 1) : 0;
        $outboundMinutes = round((clone $baseQuery)->where('call_flow', 'sip_to_trunk')->where('disposition', 'ANSWERED')->sum('billsec') / 60, 1);
        $outboundCost = (clone $baseQuery)->where('call_flow', 'sip_to_trunk')->where('status', 'rated')->sum('total_cost');

        // Disposition breakdown
        $dispositions = (clone $baseQuery)
            ->selectRaw('disposition, COUNT(*) as count')
            ->groupBy('disposition')
            ->orderByDesc('count')
            ->get()
            ->pluck('count', 'disposition')
            ->toArray();

        // Hourly distribution for chart
        $hourlyStats = (clone $baseQuery)
            ->selectRaw('HOUR(call_start) as hour, call_flow, COUNT(*) as count')
            ->groupBy('hour', 'call_flow')
            ->orderBy('hour')
            ->get();

        // Top destinations (outbound)
        $topDestinations = (clone $baseQuery)
            ->where('call_flow', 'sip_to_trunk')
            ->selectRaw('LEFT(callee, 3) as prefix, COUNT(*) as count, SUM(billsec) as duration')
            ->groupBy('prefix')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Top SIP accounts
        $topSipAccounts = (clone $baseQuery)
            ->whereNotNull('sip_account_id')
            ->selectRaw('sip_account_id, COUNT(*) as call_count, SUM(billsec) as duration')
            ->groupBy('sip_account_id')
            ->orderByDesc('call_count')
            ->limit(10)
            ->with('sipAccount')
            ->get();

        // Top trunks
        $topTrunks = (clone $baseQuery)
            ->selectRaw('
                COALESCE(outgoing_trunk_id, incoming_trunk_id) as trunk_id,
                call_flow,
                COUNT(*) as call_count,
                SUM(billsec) as duration
            ')
            ->groupBy('trunk_id', 'call_flow')
            ->orderByDesc('call_count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $item->trunk = Trunk::find($item->trunk_id);
                return $item;
            })
            ->filter(fn($item) => $item->trunk !== null);

        return view('admin.operational-reports.summary', compact(
            'dateFrom',
            'dateTo',
            'totalCalls',
            'answeredCalls',
            'asr',
            'totalMinutes',
            'totalCost',
            'inboundTotal',
            'inboundAnswered',
            'inboundAsr',
            'inboundMinutes',
            'outboundTotal',
            'outboundAnswered',
            'outboundAsr',
            'outboundMinutes',
            'outboundCost',
            'dispositions',
            'hourlyStats',
            'topDestinations',
            'topSipAccounts',
            'topTrunks'
        ));
    }
}

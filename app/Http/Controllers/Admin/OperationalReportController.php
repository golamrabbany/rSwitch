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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

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
                COALESCE(SUM(CASE WHEN disposition = "ANSWERED" THEN duration ELSE 0 END), 0) as duration
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
        $query = CallRecord::with(['user', 'sipAccount.user', 'incomingTrunk', 'outgoingTrunk', 'did'])
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

        // Live answered/ringing state comes from the engine, not the DB — see
        // liveCallStates(). The call_state filter is therefore applied to the
        // collection below rather than to the query.
        $live = $this->liveCallStates();

        // Paginate with 100 per page for large lists
        $calls = $query->orderBy('call_start', 'desc')->paginate(100);

        // Add call_state attribute to each call
        $calls->getCollection()->transform(function ($call) use ($live) {
            if ($live['available']) {
                $call->call_state = $live['map'][$call->caller . '|' . $call->callee] ?? 'processing';
            } else {
                // Engine unreachable — fall back to the stored disposition. It is
                // a placeholder on in-progress rows, so this over-reports
                // "answered", but it keeps the page no worse than before.
                $call->call_state = $call->disposition === 'ANSWERED' || $call->billsec > 0
                    ? 'answered'
                    : 'ringing';
            }
            return $call;
        });

        // Filter by call state. In-progress calls run in the tens and the page
        // size is 100, so they occupy a single page and filtering the collection
        // does not lose rows.
        if ($request->filled('call_state')) {
            $wanted = $request->call_state;
            $calls->setCollection(
                $calls->getCollection()->filter(fn ($call) => $call->call_state === $wanted)->values()
            );
        }

        // Stats. Take the whole header from the engine when it is reachable so
        // the counters agree with each other: a ringing call often has no
        // call_records row yet, so mixing DB totals with engine state would
        // leave total < answered + ringing.
        if ($live['available']) {
            $totalActive = $live['total'];
            $inboundActive = $live['inbound'];
            $outboundActive = $live['outbound'];
            $answeredCount = $live['answered'];
            $ringingCount = $live['ringing'];
        } else {
            $totalActive = CallRecord::where('status', 'in_progress')->count();
            $inboundActive = CallRecord::where('status', 'in_progress')
                ->where('call_flow', 'trunk_to_sip')->count();
            $outboundActive = CallRecord::where('status', 'in_progress')
                ->where('call_flow', 'sip_to_trunk')->count();
            $answeredCount = $totalActive;
            $ringingCount = 0;
        }

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
     * Live answered/ringing state for in-progress calls, from the Python engine.
     *
     * call_records cannot answer this. Rows are inserted at call setup with a
     * hardcoded placeholder disposition='ANSWERED' (see outbound_handler.py),
     * and duration/billsec both stay 0 until hangup, so "ringing" is simply not
     * derivable from the table -- counting `disposition IS NULL` always returned
     * zero. The AMI listener holds the only live channel state, and it is
     * already what this page's /ws/live-calls feed streams, so sourcing the
     * initial render from it also stops the page contradicting itself a second
     * after load.
     *
     * @return array{map: array<string, string>, answered: int, ringing: int, available: bool}
     */
    private function liveCallStates(): array
    {
        $unavailable = ['map' => [], 'answered' => 0, 'ringing' => 0, 'available' => false];

        try {
            $response = Http::timeout(3)->get(
                rtrim(config('services.python_api.url', 'http://127.0.0.1:8001'), '/') . '/api/active-calls'
            );

            if (! $response->successful()) {
                Log::warning('Active calls: engine returned HTTP ' . $response->status());
                return $unavailable;
            }

            $liveCalls = $response->json('calls') ?? [];
        } catch (\Throwable $e) {
            Log::warning('Active calls: could not reach engine for live state: ' . $e->getMessage());
            return $unavailable;
        }

        $map = [];
        $answered = 0;
        $ringing = 0;
        $inbound = 0;
        $outbound = 0;

        foreach ($liveCalls as $liveCall) {
            $state = ($liveCall['state'] ?? '') === 'answered' ? 'answered' : 'ringing';
            $state === 'answered' ? $answered++ : $ringing++;

            ($liveCall['call_flow'] ?? '') === 'inbound' ? $inbound++ : $outbound++;

            // caller|callee pairs the engine's view to the CDR row. Both legs of
            // one conversation share a state, so a collision is harmless.
            $map[($liveCall['caller'] ?? '') . '|' . ($liveCall['callee'] ?? '')] = $state;
        }

        return [
            'map' => $map,
            'answered' => $answered,
            'ringing' => $ringing,
            'inbound' => $inbound,
            'outbound' => $outbound,
            'total' => count($liveCalls),
            'available' => true,
        ];
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

        // Reseller filter
        if ($request->filled('reseller_id')) {
            $reseller = User::find($request->reseller_id);
            if ($reseller) {
                $query->whereIn('user_id', $reseller->descendantIds());
            }
        }

        // Client filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Caller ID filter
        if ($request->filled('caller_id')) {
            $query->where('caller', 'like', $request->caller_id . '%');
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
        $answeredCalls = (clone $statsQuery)->where('disposition', 'ANSWERED')->where('duration', '>', 0)->count();
        $asr = $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 1) : 0;
        $totalMinutes = round((clone $statsQuery)->where('disposition', 'ANSWERED')->sum('duration') / 60, 1);

        $trunks = Trunk::whereIn('direction', ['incoming', 'both'])->orderBy('name')->get();
        $resellers = User::where('role', 'reseller')->orderBy('name')->get(['id', 'name', 'email']);
        $clients = User::where('role', 'client')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.operational-reports.inbound', compact(
            'calls',
            'totalCalls',
            'answeredCalls',
            'asr',
            'totalMinutes',
            'trunks',
            'resellers',
            'clients'
        ));
    }

    public function exportInboundCalls(Request $request)
    {
        $query = CallRecord::with(['user', 'sipAccount', 'incomingTrunk', 'did'])
            ->where('call_flow', 'trunk_to_sip');

        if ($request->filled('date_from')) {
            $query->where('call_start', '>=', $request->date_from);
        } else {
            $query->where('call_start', '>=', now()->startOfDay());
        }
        if ($request->filled('date_to')) {
            $query->where('call_start', '<=', $request->date_to . ' 23:59:59');
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller', 'like', $search . '%')->orWhere('callee', 'like', $search . '%');
            });
        }
        if ($request->filled('disposition')) {
            $query->where('disposition', $request->disposition);
        }
        if ($request->filled('trunk_id')) {
            $query->where('incoming_trunk_id', $request->trunk_id);
        }

        $records = $query->orderByDesc('call_start')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inbound Calls');

        $headers = ['SL', 'Caller', 'Client', 'DID', 'Destination', 'Call Start', 'Call End', 'CDR Dur.', 'Bill Dur.', 'Trunk', 'Trunk IP', 'Status'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(chr(65 + $col) . '1', $header);
        }
        $lastCol = chr(64 + count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $row = 2;
        foreach ($records as $i => $r) {
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $r->caller);
            $sheet->setCellValue("C{$row}", $r->user?->name ?? '');
            $sheet->setCellValue("D{$row}", $r->did?->number ?? $r->callee);
            $sheet->setCellValue("E{$row}", $r->sipAccount?->username ?? '');
            $sheet->setCellValue("F{$row}", $r->call_start?->format('Y-m-d H:i:s'));
            $sheet->setCellValue("G{$row}", $r->call_end?->format('Y-m-d H:i:s') ?? '');
            $sheet->setCellValue("H{$row}", $r->duration > 0 ? gmdate('H:i:s', $r->duration) : '');
            $sheet->setCellValue("I{$row}", $r->billable_duration > 0 ? gmdate('H:i:s', $r->billable_duration) : '');
            $sheet->setCellValue("J{$row}", $r->incomingTrunk?->name ?? '');
            $sheet->setCellValue("K{$row}", $r->incomingTrunk?->host ?? '');
            $sheet->setCellValue("L{$row}", $r->disposition);

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:L{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2FF');
            }
            $row++;
        }

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $dateStr = $request->filled('date_from') ? $request->date_from : now()->format('Y-m-d');
        $filename = 'inbound-calls-' . $dateStr . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
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

        // User (client) filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Reseller filter — show calls from all clients of a reseller
        if ($request->filled('reseller_id')) {
            $reseller = \App\Models\User::find($request->reseller_id);
            if ($reseller) {
                $query->whereIn('user_id', $reseller->descendantIds());
            }
        }

        // Source IP filter
        if ($request->filled('source_ip')) {
            $sipIds = \App\Models\SipAccount::where('last_registered_ip', $request->source_ip)->pluck('id');
            $query->whereIn('sip_account_id', $sipIds);
        }

        // Caller ID filter
        if ($request->filled('caller_id')) {
            $query->where('caller', 'like', $request->caller_id . '%');
        }

        // Audio-health filter. Thresholds live here rather than in the schema so
        // they can change without a migration. NULL means "not captured" and is
        // excluded everywhere -- only 0 means "no packets arrived".
        if ($request->filled('audio')) {
            match ($request->audio) {
                // Answered, but the carrier never sent a single packet.
                'no_audio' => $query->where('billsec', '>', 0)
                    ->where('rtp_trunk_rx_count', 0),
                // Audio in one direction only, on either leg.
                'one_way' => $query->where('billsec', '>', 0)
                    ->where(function ($q) {
                        $q->where(fn ($w) => $w->where('rtp_trunk_rx_count', 0)
                                               ->where('rtp_trunk_tx_count', '>', 0))
                          ->orWhere(fn ($w) => $w->where('rtp_trunk_tx_count', 0)
                                                 ->where('rtp_trunk_rx_count', '>', 0))
                          // rtp_cust_tx_count is KNOWN UNRELIABLE (live sample: avg 213.5
                          // pkt/s vs an expected ~50, 4/26 rows implausible) -- likely the
                          // originating device aggregating its RTCP tx counter across
                          // concurrent channels. Kept because the raw data is still wanted,
                          // but treat any one_way result driven by this clause with caution.
                          ->orWhere(fn ($w) => $w->where('rtp_cust_rx_count', 0)
                                                 ->where('rtp_cust_tx_count', '>', 0))
                          ->orWhere(fn ($w) => $w->where('rtp_cust_tx_count', 0)
                                                 ->where('rtp_cust_rx_count', '>', 0));
                    }),
                // More than 5% inbound loss on either leg.
                'high_loss' => $query->where('billsec', '>', 0)
                    ->where(function ($q) {
                        $q->whereRaw('rtp_trunk_rx_loss > 0.05 * NULLIF(rtp_trunk_rx_count, 0)')
                          ->orWhereRaw('rtp_cust_rx_loss > 0.05 * NULLIF(rtp_cust_rx_count, 0)');
                    }),
                default => null,
            };
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
        // "Answered" = completed answered calls only. In-progress calls are inserted
        // with a placeholder disposition='ANSWERED' + duration=0; excluding duration=0
        // keeps live calls out of the answered count and ACD until they hang up.
        $answeredCalls = (clone $statsQuery)->where('disposition', 'ANSWERED')->where('duration', '>', 0)->count();
        $asr = $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 1) : 0;
        $totalMinutes = round((clone $statsQuery)->where('disposition', 'ANSWERED')->where('duration', '>', 0)->sum('duration') / 60, 1);
        $trunks = Trunk::whereIn('direction', ['outgoing', 'both'])->orderBy('name')->get();
        $resellers = \App\Models\User::where('role', 'reseller')->orderBy('name')->get(['id', 'name', 'email']);
        $clients = \App\Models\User::where('role', 'client')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.operational-reports.outbound', compact(
            'calls',
            'totalCalls',
            'answeredCalls',
            'asr',
            'totalMinutes',
            'trunks',
            'resellers',
            'clients'
        ));
    }

    /**
     * Call Quality — per-leg RTP analysis.
     *
     * Reads call_records directly rather than cdr_summary_hourly, which has no
     * QoS columns. Every query is bounded by call_start so the daily partitions
     * prune, and the range is capped at 31 days for the same reason.
     *
     * rtp_cust_tx_count is deliberately absent: it is known-unreliable (19,083
     * packets on a 7-second call) and this page is where someone would most
     * likely trust it.
     */
    public function qualityReport(Request $request)
    {
        $authUser = auth()->user();

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

        $scope = function ($query) use ($authUser, $dateFrom, $dateTo) {
            $query->where('call_flow', 'sip_to_trunk')
                  ->whereBetween('call_start', [$dateFrom, $dateTo]);
            if (! $authUser->isSuperAdmin()) {
                $query->whereIn('user_id', $authUser->descendantIds());
            }
            return $query;
        };

        // Summary. AVG() ignores NULLs by definition, so uncaptured rows never
        // drag an average toward zero -- but the caller still needs to know how
        // thin the sample is, hence the captured/answered coverage pair.
        $stats = $scope(CallRecord::query())->selectRaw('
            COUNT(*) as total_calls,
            SUM(billsec > 0) as answered,
            -- Coverage is only meaningful against ANSWERED calls. Unanswered calls
            -- still produce an rtcp summary (rxcount=0), so counting every row with
            -- data against the answered total gave a nonsensical >100% figure.
            SUM(billsec > 0 AND rtp_trunk_rx_count IS NOT NULL) as captured,
            AVG(rtp_trunk_rx_mes) as trunk_mes,
            AVG(rtp_cust_rx_mes) as cust_mes,
            AVG(rtp_trunk_rx_jitter) * 1000 as trunk_jitter_ms,
            AVG(rtp_cust_rx_jitter) * 1000 as cust_jitter_ms,
            AVG(rtp_trunk_rtt) * 1000 as trunk_rtt_ms,
            100 * SUM(rtp_trunk_rx_loss) / NULLIF(SUM(rtp_trunk_rx_count), 0) as trunk_loss_pct,
            100 * SUM(rtp_cust_rx_loss) / NULLIF(SUM(rtp_cust_rx_count), 0) as cust_loss_pct,
            SUM(billsec > 0 AND rtp_trunk_rx_count = 0) as no_audio,
            SUM(billsec > 0 AND rtp_trunk_rx_count > 0 AND rtp_trunk_tx_count = 0) as one_way
        ')->first();

        $coverage = $stats->answered > 0
            ? round(100 * $stats->captured / $stats->answered, 1)
            : 0.0;

        // Hourly trend — carrier vs customer jitter.
        $hourly = $scope(CallRecord::query())
            ->whereNotNull('rtp_trunk_rx_jitter')
            ->selectRaw("
                DATE_FORMAT(call_start, '%Y-%m-%d %H:00') as bucket,
                AVG(rtp_trunk_rx_jitter) * 1000 as trunk_jitter_ms,
                AVG(rtp_cust_rx_jitter) * 1000 as cust_jitter_ms,
                COUNT(*) as calls
            ")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        // Breakdown, pivotable. Worst carrier MES first; NULLs last.
        $groupBy = in_array($request->group_by, ['client', 'trunk', 'prefix'], true)
            ? $request->group_by
            : 'client';

        $breakdown = $scope(CallRecord::query())->whereNotNull('rtp_trunk_rx_count');

        $breakdown = match ($groupBy) {
            'trunk' => $breakdown
                ->leftJoin('trunks', 'trunks.id', '=', 'call_records.outgoing_trunk_id')
                ->selectRaw('COALESCE(trunks.name, "(none)") as label'),
            'prefix' => $breakdown
                ->selectRaw('LEFT(call_records.callee, 3) as label'),
            default => $breakdown
                ->leftJoin('users', 'users.id', '=', 'call_records.user_id')
                ->selectRaw('COALESCE(users.name, "(unknown)") as label'),
        };

        $breakdown = $breakdown->selectRaw('
                COUNT(*) as calls,
                SUM(call_records.billsec > 0) as answered,
                AVG(call_records.rtp_trunk_rx_mes) as trunk_mes,
                AVG(call_records.rtp_trunk_rx_jitter) * 1000 as trunk_jitter_ms,
                AVG(call_records.rtp_trunk_rtt) * 1000 as trunk_rtt_ms,
                100 * SUM(call_records.rtp_trunk_rx_loss) / NULLIF(SUM(call_records.rtp_trunk_rx_count), 0) as trunk_loss_pct,
                SUM(call_records.billsec > 0 AND call_records.rtp_trunk_rx_count = 0) as no_audio
            ')
            ->groupBy('label')
            ->orderByRaw('AVG(call_records.rtp_trunk_rx_mes) IS NULL, AVG(call_records.rtp_trunk_rx_mes) ASC')
            ->limit(50)
            ->get();

        return view('admin.operational-reports.quality', compact(
            'stats', 'coverage', 'hourly', 'breakdown', 'groupBy', 'dateFrom', 'dateTo'
        ));
    }

    public function exportOutboundCalls(Request $request)
    {
        $query = CallRecord::with(['user', 'sipAccount', 'outgoingTrunk'])
            ->where('call_flow', 'sip_to_trunk');

        if ($request->filled('date_from')) {
            $query->where('call_start', '>=', $request->date_from);
        } else {
            $query->where('call_start', '>=', now()->startOfDay());
        }
        if ($request->filled('date_to')) {
            $query->where('call_start', '<=', $request->date_to . ' 23:59:59');
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller', 'like', $search . '%')->orWhere('callee', 'like', $search . '%');
            });
        }
        if ($request->filled('disposition')) {
            $query->where('disposition', $request->disposition);
        }
        if ($request->filled('trunk_id')) {
            $query->where('outgoing_trunk_id', $request->trunk_id);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('reseller_id')) {
            $reseller = User::find($request->reseller_id);
            if ($reseller) {
                $query->whereIn('user_id', $reseller->descendantIds());
            }
        }
        if ($request->filled('source_ip')) {
            $sipIds = SipAccount::where('last_registered_ip', $request->source_ip)->pluck('id');
            $query->whereIn('sip_account_id', $sipIds);
        }
        if ($request->filled('caller_id')) {
            $query->where('caller', 'like', $request->caller_id . '%');
        }

        $records = $query->orderByDesc('call_start')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Outbound Calls');

        $headers = ['SL', 'SIP Account', 'Client', 'Caller ID', 'Source IP', 'Destination', 'Call Start', 'Call End', 'CDR Dur.', 'Bill Dur.', 'Trunk', 'Trunk IP', 'Status'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(chr(65 + $col) . '1', $header);
        }
        $lastCol = chr(64 + count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $row = 2;
        foreach ($records as $i => $r) {
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $r->sipAccount?->username ?? '');
            $sheet->setCellValue("C{$row}", $r->user?->name ?? '');
            $sheet->setCellValue("D{$row}", $r->caller_id ?: $r->caller);
            $sheet->setCellValue("E{$row}", $r->sipAccount?->last_registered_ip ?? '');
            $sheet->setCellValue("F{$row}", $r->callee);
            $sheet->setCellValue("G{$row}", $r->call_start?->format('Y-m-d H:i:s'));
            $sheet->setCellValue("H{$row}", $r->call_end?->format('Y-m-d H:i:s') ?? '');
            $sheet->setCellValue("I{$row}", $r->duration > 0 ? gmdate('H:i:s', $r->duration) : '');
            $sheet->setCellValue("J{$row}", $r->billable_duration > 0 ? gmdate('H:i:s', $r->billable_duration) : '');
            $sheet->setCellValue("K{$row}", $r->outgoingTrunk?->name ?? '');
            $sheet->setCellValue("L{$row}", $r->outgoingTrunk?->host ?? '');
            $sheet->setCellValue("M{$row}", $r->disposition);

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:M{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2FF');
            }
            $row++;
        }

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $dateStr = $request->filled('date_from') ? $request->date_from : now()->format('Y-m-d');
        $filename = 'outbound-calls-' . $dateStr . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Transit Calls List (Trunk-to-Trunk)
     */
    public function transitCalls(Request $request)
    {
        $query = CallRecord::with(['incomingTrunk', 'outgoingTrunk'])
            ->where('call_flow', 'trunk_to_trunk');

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('call_start', '>=', $request->date_from);
        } else {
            $query->where('call_start', '>=', now()->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('call_start', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller', 'like', $search . '%')
                  ->orWhere('callee', 'like', $search . '%');
            });
        }

        if ($request->filled('disposition')) {
            $query->where('disposition', $request->disposition);
        }

        if ($request->filled('incoming_trunk_id')) {
            $query->where('incoming_trunk_id', $request->incoming_trunk_id);
        }

        if ($request->filled('outgoing_trunk_id')) {
            $query->where('outgoing_trunk_id', $request->outgoing_trunk_id);
        }

        $calls = $query->orderBy('call_start', 'desc')->paginate(50);

        // Stats
        $statsQuery = CallRecord::where('call_flow', 'trunk_to_trunk');
        if ($request->filled('date_from')) {
            $statsQuery->where('call_start', '>=', $request->date_from);
        } else {
            $statsQuery->where('call_start', '>=', now()->startOfDay());
        }
        if ($request->filled('date_to')) {
            $statsQuery->where('call_start', '<=', $request->date_to . ' 23:59:59');
        }

        $totalCalls = (clone $statsQuery)->count();
        $answeredCalls = (clone $statsQuery)->where('disposition', 'ANSWERED')->where('duration', '>', 0)->count();
        $asr = $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 1) : 0;
        $totalMinutes = round((clone $statsQuery)->where('disposition', 'ANSWERED')->sum('duration') / 60, 1);

        $incomingTrunks = Trunk::whereIn('direction', ['incoming', 'both'])->orderBy('name')->get();
        $outgoingTrunks = Trunk::whereIn('direction', ['outgoing', 'both'])->orderBy('name')->get();

        return view('admin.operational-reports.transit', compact(
            'calls', 'totalCalls', 'answeredCalls', 'asr', 'totalMinutes',
            'incomingTrunks', 'outgoingTrunks'
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
            SUM(disposition = "ANSWERED" AND duration > 0) as answered_calls,
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

        // Overall Stats — answered = completed answered calls only (exclude
        // duration=0 in-progress placeholders); ACD from exact billsec so it
        // doesn't drift from the rounded minutes value.
        $totalCalls = (clone $baseQuery)->count();
        $answeredCalls = (clone $baseQuery)->where('disposition', 'ANSWERED')->where('duration', '>', 0)->count();
        $asr = $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 1) : 0;
        $totalBillsec = (int) (clone $baseQuery)->where('disposition', 'ANSWERED')->where('duration', '>', 0)->sum('duration');
        $totalMinutes = round($totalBillsec / 60, 1);
        $acd = $answeredCalls > 0 ? (int) round($totalBillsec / $answeredCalls) : 0;

        // Inbound Stats
        $inboundTotal = (clone $baseQuery)->where('call_flow', 'trunk_to_sip')->count();
        $inboundAnswered = (clone $baseQuery)->where('call_flow', 'trunk_to_sip')->where('disposition', 'ANSWERED')->where('duration', '>', 0)->count();
        $inboundAsr = $inboundTotal > 0 ? round(($inboundAnswered / $inboundTotal) * 100, 1) : 0;
        $inboundBillsec = (int) (clone $baseQuery)->where('call_flow', 'trunk_to_sip')->where('disposition', 'ANSWERED')->where('duration', '>', 0)->sum('duration');
        $inboundMinutes = round($inboundBillsec / 60, 1);
        $inboundAcd = $inboundAnswered > 0 ? (int) round($inboundBillsec / $inboundAnswered) : 0;

        // Outbound Stats
        $outboundTotal = (clone $baseQuery)->where('call_flow', 'sip_to_trunk')->count();
        $outboundAnswered = (clone $baseQuery)->where('call_flow', 'sip_to_trunk')->where('disposition', 'ANSWERED')->where('duration', '>', 0)->count();
        $outboundAsr = $outboundTotal > 0 ? round(($outboundAnswered / $outboundTotal) * 100, 1) : 0;
        $outboundBillsec = (int) (clone $baseQuery)->where('call_flow', 'sip_to_trunk')->where('disposition', 'ANSWERED')->where('duration', '>', 0)->sum('duration');
        $outboundMinutes = round($outboundBillsec / 60, 1);
        $outboundAcd = $outboundAnswered > 0 ? (int) round($outboundBillsec / $outboundAnswered) : 0;
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
            'acd',
            'inboundTotal',
            'inboundAnswered',
            'inboundAsr',
            'inboundMinutes',
            'inboundAcd',
            'outboundTotal',
            'outboundAnswered',
            'outboundAsr',
            'outboundMinutes',
            'outboundAcd',
            'dispositions',
            'hourlyStats',
            'topSipAccounts',
            'topTrunks'
        ));
    }

    /**
     * Daily Summary Report
     */
    public function dailySummary(Request $request)
    {
        $authUser = auth()->user();

        // Date range (default: last 7 days, max 365 days)
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::today()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            $dateFrom = $dateTo->copy()->startOfDay();
        }

        if ($dateFrom->diffInDays($dateTo) > 365) {
            $dateFrom = $dateTo->copy()->subDays(365)->startOfDay();
        }

        // Use cdr_summary_daily (fast) unless trunk filter is applied (needs raw CDR)
        $useSummary = !$request->filled('trunk_id');

        if ($useSummary) {
            $query = DB::table('cdr_summary_daily')
                ->whereBetween('date', [$dateFrom->toDateString(), $dateTo->toDateString()]);

            if (!$authUser->isSuperAdmin()) {
                $query->whereIn('user_id', $authUser->descendantIds());
            }
            if ($request->filled('reseller_id')) {
                $query->where('reseller_id', $request->reseller_id);
            }
            if ($request->filled('client_id')) {
                $query->where('user_id', $request->client_id);
            }

            $rows = $query->selectRaw('
                    date,
                    SUM(total_calls) as total_calls,
                    SUM(answered_calls) as answered_calls,
                    SUM(total_calls) - SUM(answered_calls) as failed_calls,
                    SUM(total_duration) as total_billsec
                ')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } else {
            // Trunk filter — must use call_records
            $query = CallRecord::query()
                ->whereBetween('call_start', [$dateFrom, $dateTo]);

            if (!$authUser->isSuperAdmin()) {
                $query->whereIn('user_id', $authUser->descendantIds());
            }
            $this->applyFilters($query, $request, $authUser);

            $rows = (clone $query)
                ->selectRaw('
                    DATE(call_start) as date,
                    COUNT(*) as total_calls,
                    SUM(disposition = "ANSWERED" AND duration > 0) as answered_calls,
                    SUM(disposition != "ANSWERED") as failed_calls,
                    COALESCE(SUM(CASE WHEN disposition = "ANSWERED" THEN duration ELSE 0 END), 0) as total_billsec
                ')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }

        // Compute ASR and ACD per row
        $rows->transform(function ($row) {
            $row->asr = $row->total_calls > 0 ? round(($row->answered_calls / $row->total_calls) * 100, 1) : 0;
            $row->acd = $row->answered_calls > 0 ? round($row->total_billsec / $row->answered_calls) : 0;
            $row->minutes = round($row->total_billsec / 60, 1);
            return $row;
        });

        // Totals (keep exact total_billsec so ACD is precise, not derived from
        // the rounded minutes value).
        $totalBillsec = $rows->sum('total_billsec');
        $totals = [
            'total_calls' => $rows->sum('total_calls'),
            'answered_calls' => $rows->sum('answered_calls'),
            'failed_calls' => $rows->sum('failed_calls'),
            'total_billsec' => $totalBillsec,
            'minutes' => round($totalBillsec / 60, 1),
        ];
        $totals['asr'] = $totals['total_calls'] > 0 ? round(($totals['answered_calls'] / $totals['total_calls']) * 100, 1) : 0;
        $totals['acd'] = $totals['answered_calls'] > 0 ? (int) round($totalBillsec / $totals['answered_calls']) : 0;

        // Chart data — keep chronological (ASC) regardless of table order.
        $chartLabels = $rows->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d'))->values();
        $chartTotal = $rows->pluck('total_calls')->values();
        $chartAnswered = $rows->pluck('answered_calls')->values();

        // Table shows newest day first (DESC); the chart above stays chronological.
        $rows = $rows->reverse()->values();

        // Filter options
        [$resellers, $trunks] = $this->getFilterOptions($authUser);

        $clients = User::where('role', 'client')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.operational-reports.daily-summary', compact(
            'rows', 'totals', 'dateFrom', 'dateTo',
            'chartLabels', 'chartTotal', 'chartAnswered',
            'resellers', 'clients', 'trunks'
        ));
    }

    /**
     * Monthly Summary Report
     */
    public function monthlySummary(Request $request)
    {
        $authUser = auth()->user();

        // Date range (default: last 7 months)
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfMonth()
            : Carbon::now()->subMonths(6)->startOfMonth();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfMonth()
            : Carbon::now()->endOfMonth();

        if ($dateFrom->gt($dateTo)) {
            $dateFrom = $dateTo->copy()->startOfMonth();
        }

        // Use cdr_summary_daily (fast) unless trunk filter is applied
        $useSummary = !$request->filled('trunk_id');

        if ($useSummary) {
            $query = DB::table('cdr_summary_daily')
                ->whereBetween('date', [$dateFrom->toDateString(), $dateTo->toDateString()]);

            if (!$authUser->isSuperAdmin()) {
                $query->whereIn('user_id', $authUser->descendantIds());
            }
            if ($request->filled('reseller_id')) {
                $query->where('reseller_id', $request->reseller_id);
            }
            if ($request->filled('client_id')) {
                $query->where('user_id', $request->client_id);
            }

            $rows = $query->selectRaw('
                    YEAR(date) as year,
                    MONTH(date) as month,
                    SUM(total_calls) as total_calls,
                    SUM(answered_calls) as answered_calls,
                    SUM(total_calls) - SUM(answered_calls) as failed_calls,
                    SUM(total_duration) as total_billsec
                ')
                ->groupByRaw('YEAR(date), MONTH(date)')
                ->orderByRaw('YEAR(date), MONTH(date)')
                ->get();
        } else {
            $query = CallRecord::query()
                ->whereBetween('call_start', [$dateFrom, $dateTo]);

            if (!$authUser->isSuperAdmin()) {
                $query->whereIn('user_id', $authUser->descendantIds());
            }
            $this->applyFilters($query, $request, $authUser);

            $rows = (clone $query)
                ->selectRaw('
                    YEAR(call_start) as year,
                    MONTH(call_start) as month,
                    COUNT(*) as total_calls,
                    SUM(disposition = "ANSWERED" AND duration > 0) as answered_calls,
                    SUM(disposition != "ANSWERED") as failed_calls,
                    COALESCE(SUM(CASE WHEN disposition = "ANSWERED" THEN duration ELSE 0 END), 0) as total_billsec
                ')
                ->groupByRaw('YEAR(call_start), MONTH(call_start)')
                ->orderByRaw('YEAR(call_start), MONTH(call_start)')
                ->get();
        }

        // Compute ASR, ACD + month-over-month change
        $prevTotal = null;
        $rows->transform(function ($row) use (&$prevTotal) {
            $row->asr = $row->total_calls > 0 ? round(($row->answered_calls / $row->total_calls) * 100, 1) : 0;
            $row->acd = $row->answered_calls > 0 ? round($row->total_billsec / $row->answered_calls) : 0;
            $row->minutes = round($row->total_billsec / 60, 1);
            $row->month_label = Carbon::create($row->year, $row->month, 1)->format('M Y');

            if ($prevTotal !== null && $prevTotal > 0) {
                $row->change = round((($row->total_calls - $prevTotal) / $prevTotal) * 100, 1);
            } else {
                $row->change = null;
            }
            $prevTotal = $row->total_calls;

            return $row;
        });

        // Totals (keep exact total_billsec so ACD is precise, not derived from
        // the rounded minutes value).
        $totalBillsec = $rows->sum('total_billsec');
        $totals = [
            'total_calls' => $rows->sum('total_calls'),
            'answered_calls' => $rows->sum('answered_calls'),
            'failed_calls' => $rows->sum('failed_calls'),
            'total_billsec' => $totalBillsec,
            'minutes' => round($totalBillsec / 60, 1),
        ];
        $totals['asr'] = $totals['total_calls'] > 0 ? round(($totals['answered_calls'] / $totals['total_calls']) * 100, 1) : 0;
        $totals['acd'] = $totals['answered_calls'] > 0 ? (int) round($totalBillsec / $totals['answered_calls']) : 0;

        // Chart data — keep chronological (ASC) regardless of table order.
        $chartLabels = $rows->pluck('month_label')->values();
        $chartTotal = $rows->pluck('total_calls')->values();
        $chartAnswered = $rows->pluck('answered_calls')->values();

        // Table shows newest month first (DESC); the chart and the
        // month-over-month change (computed above in chronological order) are
        // unaffected.
        $rows = $rows->reverse()->values();

        // Filter options
        [$resellers, $trunks] = $this->getFilterOptions($authUser);

        $clients = User::where('role', 'client')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.operational-reports.monthly-summary', compact(
            'rows', 'totals', 'dateFrom', 'dateTo',
            'chartLabels', 'chartTotal', 'chartAnswered',
            'resellers', 'clients', 'trunks'
        ));
    }

    /**
     * Hourly Summary Report
     */
    public function hourlySummary(Request $request)
    {
        $authUser = auth()->user();

        // Date range (default: today)
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::today()->startOfDay();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::today()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            $dateFrom = $dateTo->copy()->startOfDay();
        }

        // Limit to 31 days for hourly to keep results meaningful
        if ($dateFrom->diffInDays($dateTo) > 31) {
            $dateTo = $dateFrom->copy()->addDays(31)->endOfDay();
        }

        // Use cdr_summary_hourly (fast) unless trunk filter is applied
        $useSummary = !$request->filled('trunk_id');

        if ($useSummary) {
            $query = DB::table('cdr_summary_hourly')
                ->whereBetween('hour_start', [$dateFrom, $dateTo]);

            if (!$authUser->isSuperAdmin()) {
                $query->whereIn('user_id', $authUser->descendantIds());
            }
            if ($request->filled('reseller_id')) {
                $query->where('reseller_id', $request->reseller_id);
            }
            if ($request->filled('client_id')) {
                $query->where('user_id', $request->client_id);
            }

            $rawRows = $query->selectRaw('
                    HOUR(hour_start) as hour,
                    SUM(total_calls) as total_calls,
                    SUM(answered_calls) as answered_calls,
                    SUM(failed_calls) as failed_calls,
                    SUM(total_duration) as total_billsec
                ')
                ->groupByRaw('HOUR(hour_start)')
                ->orderByRaw('HOUR(hour_start)')
                ->get()
                ->keyBy('hour');
        } else {
            $query = CallRecord::query()
                ->whereBetween('call_start', [$dateFrom, $dateTo]);

            if (!$authUser->isSuperAdmin()) {
                $query->whereIn('user_id', $authUser->descendantIds());
            }
            $this->applyFilters($query, $request, $authUser);

            $rawRows = (clone $query)
                ->selectRaw('
                    HOUR(call_start) as hour,
                    COUNT(*) as total_calls,
                    SUM(disposition = "ANSWERED" AND duration > 0) as answered_calls,
                    SUM(disposition != "ANSWERED") as failed_calls,
                    COALESCE(SUM(CASE WHEN disposition = "ANSWERED" THEN duration ELSE 0 END), 0) as total_billsec
                ')
                ->groupByRaw('HOUR(call_start)')
                ->orderByRaw('HOUR(call_start)')
                ->get()
                ->keyBy('hour');
        }

        // Build hour array — only up to current hour if viewing today
        $maxHour = $dateFrom->isToday() ? now()->hour + 1 : 24;
        $rows = collect();
        for ($h = 0; $h < $maxHour; $h++) {
            if ($rawRows->has($h)) {
                $row = $rawRows[$h];
            } else {
                $row = (object) [
                    'hour' => $h,
                    'total_calls' => 0,
                    'answered_calls' => 0,
                    'failed_calls' => 0,
                    'total_billsec' => 0,
                ];
            }
            $row->asr = $row->total_calls > 0 ? round(($row->answered_calls / $row->total_calls) * 100, 1) : 0;
            $row->acd = $row->answered_calls > 0 ? round($row->total_billsec / $row->answered_calls) : 0;
            $row->minutes = round($row->total_billsec / 60, 1);
            $row->hour_label = sprintf('%02d:00 – %02d:00', $h, ($h + 1) % 24);
            $rows->push($row);
        }

        // Totals (keep exact total_billsec so ACD is precise, not derived from
        // the rounded minutes value).
        $totalBillsec = $rows->sum('total_billsec');
        $totals = [
            'total_calls' => $rows->sum('total_calls'),
            'answered_calls' => $rows->sum('answered_calls'),
            'failed_calls' => $rows->sum('failed_calls'),
            'total_billsec' => $totalBillsec,
            'minutes' => round($totalBillsec / 60, 1),
        ];
        $totals['asr'] = $totals['total_calls'] > 0 ? round(($totals['answered_calls'] / $totals['total_calls']) * 100, 1) : 0;
        $totals['acd'] = $totals['answered_calls'] > 0 ? (int) round($totalBillsec / $totals['answered_calls']) : 0;

        // Chart data — keep chronological (ASC) regardless of table order.
        $chartLabels = $rows->map(fn($r) => sprintf('%02d:00', $r->hour))->values();
        $chartAnswered = $rows->pluck('answered_calls')->values();
        $chartFailed = $rows->pluck('failed_calls')->values();

        // Table shows newest hour first (DESC); the chart above stays chronological.
        $rows = $rows->reverse()->values();

        // Filter options
        [$resellers, $trunks] = $this->getFilterOptions($authUser);

        $clients = User::where('role', 'client')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.operational-reports.hourly-summary', compact(
            'rows', 'totals', 'dateFrom', 'dateTo',
            'chartLabels', 'chartAnswered', 'chartFailed',
            'resellers', 'clients', 'trunks'
        ));
    }

    /**
     * Export Daily Summary as XLSX
     */
    public function exportDailySummary(Request $request)
    {
        $authUser = auth()->user();

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::today()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            $dateFrom = $dateTo->copy()->startOfDay();
        }
        if ($dateFrom->diffInDays($dateTo) > 365) {
            $dateFrom = $dateTo->copy()->subDays(365)->startOfDay();
        }

        $query = CallRecord::query()->whereBetween('call_start', [$dateFrom, $dateTo]);
        if (!$authUser->isSuperAdmin()) {
            $query->whereIn('user_id', $authUser->descendantIds());
        }
        $this->applyFilters($query, $request, $authUser);

        $rows = (clone $query)
            ->selectRaw('
                DATE(call_start) as date,
                COUNT(*) as total_calls,
                SUM(disposition = "ANSWERED" AND duration > 0) as answered_calls,
                SUM(disposition != "ANSWERED") as failed_calls,
                COALESCE(SUM(CASE WHEN disposition = "ANSWERED" THEN duration ELSE 0 END), 0) as total_billsec
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $rows->transform(function ($row) {
            $row->asr = $row->total_calls > 0 ? round(($row->answered_calls / $row->total_calls) * 100, 1) : 0;
            $row->acd = $row->answered_calls > 0 ? round($row->total_billsec / $row->answered_calls) : 0;
            $row->minutes = round($row->total_billsec / 60, 1);
            return $row;
        });

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daily Summary');

        $headers = ['#', 'Date', 'Total Calls', 'Answered', 'Failed', 'ASR%', 'ACD', 'Minutes'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(chr(65 + $col) . '1', $header);
        }
        $lastCol = chr(64 + count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $rowNum = 2;
        foreach ($rows as $i => $r) {
            $sheet->setCellValue("A{$rowNum}", $i + 1);
            $sheet->setCellValue("B{$rowNum}", Carbon::parse($r->date)->format('Y-m-d'));
            $sheet->setCellValue("C{$rowNum}", $r->total_calls);
            $sheet->setCellValue("D{$rowNum}", $r->answered_calls);
            $sheet->setCellValue("E{$rowNum}", $r->failed_calls);
            $sheet->setCellValue("F{$rowNum}", $r->asr . '%');
            $sheet->setCellValue("G{$rowNum}", $r->acd > 0 ? sprintf('%d:%02d', intdiv($r->acd, 60), $r->acd % 60) : '0:00');
            $sheet->setCellValue("H{$rowNum}", $r->minutes);

            if ($rowNum % 2 === 0) {
                $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2FF');
            }
            $rowNum++;
        }

        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'daily-summary-' . $dateFrom->format('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Export Monthly Summary as XLSX
     */
    public function exportMonthlySummary(Request $request)
    {
        $authUser = auth()->user();

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfMonth()
            : Carbon::now()->subMonths(6)->startOfMonth();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfMonth()
            : Carbon::now()->endOfMonth();

        if ($dateFrom->gt($dateTo)) {
            $dateFrom = $dateTo->copy()->startOfMonth();
        }

        $query = CallRecord::query()->whereBetween('call_start', [$dateFrom, $dateTo]);
        if (!$authUser->isSuperAdmin()) {
            $query->whereIn('user_id', $authUser->descendantIds());
        }
        $this->applyFilters($query, $request, $authUser);

        $rows = (clone $query)
            ->selectRaw('
                YEAR(call_start) as year,
                MONTH(call_start) as month,
                COUNT(*) as total_calls,
                SUM(disposition = "ANSWERED" AND duration > 0) as answered_calls,
                SUM(disposition != "ANSWERED") as failed_calls,
                COALESCE(SUM(CASE WHEN disposition = "ANSWERED" THEN duration ELSE 0 END), 0) as total_billsec
            ')
            ->groupByRaw('YEAR(call_start), MONTH(call_start)')
            ->orderByRaw('YEAR(call_start), MONTH(call_start)')
            ->get();

        $rows->transform(function ($row) {
            $row->asr = $row->total_calls > 0 ? round(($row->answered_calls / $row->total_calls) * 100, 1) : 0;
            $row->acd = $row->answered_calls > 0 ? round($row->total_billsec / $row->answered_calls) : 0;
            $row->minutes = round($row->total_billsec / 60, 1);
            $row->month_label = Carbon::create($row->year, $row->month, 1)->format('M Y');
            return $row;
        });

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Monthly Summary');

        $headers = ['#', 'Month', 'Total Calls', 'Answered', 'Failed', 'ASR%', 'ACD', 'Minutes'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(chr(65 + $col) . '1', $header);
        }
        $lastCol = chr(64 + count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $rowNum = 2;
        foreach ($rows as $i => $r) {
            $sheet->setCellValue("A{$rowNum}", $i + 1);
            $sheet->setCellValue("B{$rowNum}", $r->month_label);
            $sheet->setCellValue("C{$rowNum}", $r->total_calls);
            $sheet->setCellValue("D{$rowNum}", $r->answered_calls);
            $sheet->setCellValue("E{$rowNum}", $r->failed_calls);
            $sheet->setCellValue("F{$rowNum}", $r->asr . '%');
            $sheet->setCellValue("G{$rowNum}", $r->acd > 0 ? sprintf('%d:%02d', intdiv($r->acd, 60), $r->acd % 60) : '0:00');
            $sheet->setCellValue("H{$rowNum}", $r->minutes);

            if ($rowNum % 2 === 0) {
                $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2FF');
            }
            $rowNum++;
        }

        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'monthly-summary-' . $dateFrom->format('Y-m') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Export Hourly Summary as XLSX
     */
    public function exportHourlySummary(Request $request)
    {
        $authUser = auth()->user();

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

        $query = CallRecord::query()->whereBetween('call_start', [$dateFrom, $dateTo]);
        if (!$authUser->isSuperAdmin()) {
            $query->whereIn('user_id', $authUser->descendantIds());
        }
        $this->applyFilters($query, $request, $authUser);

        $rawRows = (clone $query)
            ->selectRaw('
                HOUR(call_start) as hour,
                COUNT(*) as total_calls,
                SUM(disposition = "ANSWERED" AND duration > 0) as answered_calls,
                SUM(disposition != "ANSWERED") as failed_calls,
                COALESCE(SUM(CASE WHEN disposition = "ANSWERED" THEN duration ELSE 0 END), 0) as total_billsec
            ')
            ->groupByRaw('HOUR(call_start)')
            ->orderByRaw('HOUR(call_start)')
            ->get()
            ->keyBy('hour');

        $rows = collect();
        for ($h = 0; $h < 24; $h++) {
            if ($rawRows->has($h)) {
                $row = $rawRows[$h];
            } else {
                $row = (object) [
                    'hour' => $h,
                    'total_calls' => 0,
                    'answered_calls' => 0,
                    'failed_calls' => 0,
                    'total_billsec' => 0,
                ];
            }
            $row->asr = $row->total_calls > 0 ? round(($row->answered_calls / $row->total_calls) * 100, 1) : 0;
            $row->acd = $row->answered_calls > 0 ? round($row->total_billsec / $row->answered_calls) : 0;
            $row->minutes = round($row->total_billsec / 60, 1);
            $row->hour_label = sprintf('%02d:00 - %02d:00', $h, ($h + 1) % 24);
            $rows->push($row);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Hourly Summary');

        $headers = ['#', 'Hour', 'Total Calls', 'Answered', 'Failed', 'ASR%', 'ACD', 'Minutes'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(chr(65 + $col) . '1', $header);
        }
        $lastCol = chr(64 + count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $rowNum = 2;
        foreach ($rows as $i => $r) {
            $sheet->setCellValue("A{$rowNum}", $i + 1);
            $sheet->setCellValue("B{$rowNum}", $r->hour_label);
            $sheet->setCellValue("C{$rowNum}", $r->total_calls);
            $sheet->setCellValue("D{$rowNum}", $r->answered_calls);
            $sheet->setCellValue("E{$rowNum}", $r->failed_calls);
            $sheet->setCellValue("F{$rowNum}", $r->asr . '%');
            $sheet->setCellValue("G{$rowNum}", $r->acd > 0 ? sprintf('%d:%02d', intdiv($r->acd, 60), $r->acd % 60) : '0:00');
            $sheet->setCellValue("H{$rowNum}", $r->minutes);

            if ($rowNum % 2 === 0) {
                $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2FF');
            }
            $rowNum++;
        }

        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'hourly-summary-' . $dateFrom->format('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Apply shared filters to a call_records query.
     */
    private function applyFilters($query, Request $request, User $authUser): void
    {
        // Reseller filter — include reseller + their clients
        if ($request->filled('reseller_id')) {
            $reseller = User::find($request->reseller_id);
            if ($reseller) {
                $query->whereIn('user_id', $reseller->descendantIds());
            }
        }

        // Client filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Trunk filter (incoming OR outgoing)
        if ($request->filled('trunk_id')) {
            $trunkId = $request->trunk_id;
            $query->where(function ($q) use ($trunkId) {
                $q->where('incoming_trunk_id', $trunkId)
                  ->orWhere('outgoing_trunk_id', $trunkId);
            });
        }

        // Disposition filter
        if ($request->filled('disposition')) {
            $query->where('disposition', $request->disposition);
        }

        // Call flow filter (inbound/outbound/p2p)
        if ($request->filled('call_flow')) {
            $query->where('call_flow', $request->call_flow);
        }

        // Call type filter (regular/broadcast)
        if ($request->filled('call_type')) {
            $query->where('call_type', $request->call_type);
        }
    }

    /**
     * Profit & Loss Report — grouped by reseller.
     */
    public function profitLoss(Request $request)
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::now()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            $dateFrom = $dateTo->copy()->startOfDay();
        }

        // Cap at 365 days
        if ($dateFrom->diffInDays($dateTo) > 365) {
            $dateTo = $dateFrom->copy()->addDays(365)->endOfDay();
        }

        // Use cdr_summary_daily for fast P&L aggregation
        $data = DB::table('cdr_summary_daily')
            ->whereBetween('cdr_summary_daily.date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->whereNotNull('cdr_summary_daily.reseller_id')
            ->join('users', 'users.id', '=', 'cdr_summary_daily.reseller_id')
            ->groupBy('cdr_summary_daily.reseller_id', 'users.name')
            ->selectRaw('
                cdr_summary_daily.reseller_id,
                users.name as reseller_name,
                SUM(cdr_summary_daily.total_calls) as total_calls,
                SUM(cdr_summary_daily.answered_calls) as answered_calls,
                SUM(cdr_summary_daily.total_billable) as total_billable,
                SUM(cdr_summary_daily.total_cost) as client_revenue,
                SUM(cdr_summary_daily.total_reseller_cost) as reseller_cost,
                SUM(cdr_summary_daily.total_trunk_cost) as trunk_cost
            ')
            ->orderByDesc('client_revenue')
            ->get();

        // Compute per-row derived fields
        $data->transform(function ($row) {
            $row->minutes = round($row->total_billable / 60, 2);
            // Reseller profit = client revenue - reseller cost
            $row->reseller_profit = $row->client_revenue - $row->reseller_cost;
            // Platform profit = reseller cost - trunk cost (what platform keeps)
            $row->platform_profit = $row->reseller_cost - $row->trunk_cost;
            // Total profit = client revenue - trunk cost
            $row->profit = $row->client_revenue - $row->trunk_cost;
            $row->margin_pct = $row->client_revenue > 0
                ? round(($row->profit / $row->client_revenue) * 100, 1)
                : 0;
            $row->asr = $row->total_calls > 0
                ? round(($row->answered_calls / $row->total_calls) * 100, 1)
                : 0;
            return $row;
        });

        // Totals
        $totalRevenue = $data->sum('client_revenue');
        $totalResellerCost = $data->sum('reseller_cost');
        $totalTrunkCost = $data->sum('trunk_cost');
        $totalPlatformProfit = $totalResellerCost - $totalTrunkCost;
        $totalProfit = $totalRevenue - $totalTrunkCost;
        $avgMargin = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0;
        $totalCalls = $data->sum('total_calls');
        $totalAnswered = $data->sum('answered_calls');
        $totalMinutes = $data->sum('minutes');

        // Chart data: top 10 resellers by platform profit
        $chartItems = $data->sortByDesc('platform_profit')->take(10);
        $chartLabels = $chartItems->pluck('reseller_name')->values();
        $chartData = $chartItems->pluck('platform_profit')->values();

        // Transit P&L — trunk-to-trunk calls grouped by trunk pair
        $transitData = DB::table('call_records')
            ->where('call_flow', 'trunk_to_trunk')
            ->whereBetween('call_start', [$dateFrom, $dateTo])
            ->whereNotNull('incoming_trunk_id')
            ->whereNotNull('outgoing_trunk_id')
            ->join('trunks as t_in', 't_in.id', '=', 'call_records.incoming_trunk_id')
            ->join('trunks as t_out', 't_out.id', '=', 'call_records.outgoing_trunk_id')
            ->groupBy('call_records.incoming_trunk_id', 'call_records.outgoing_trunk_id', 't_in.name', 't_out.name')
            ->selectRaw('
                call_records.incoming_trunk_id,
                call_records.outgoing_trunk_id,
                t_in.name as incoming_trunk_name,
                t_out.name as outgoing_trunk_name,
                COUNT(*) as total_calls,
                SUM(call_records.disposition = "ANSWERED") as answered_calls,
                SUM(call_records.billable_duration) as total_billable,
                SUM(call_records.total_cost) as revenue,
                SUM(call_records.trunk_cost) as cost
            ')
            ->orderByDesc('revenue')
            ->get();

        $transitData->transform(function ($row) {
            $row->minutes = round(($row->total_billable ?? 0) / 60, 2);
            $row->profit = $row->revenue - $row->cost;
            $row->margin_pct = $row->revenue > 0 ? round(($row->profit / $row->revenue) * 100, 1) : 0;
            return $row;
        });

        $transitTotalRevenue = $transitData->sum('revenue');
        $transitTotalCost = $transitData->sum('cost');
        $transitTotalProfit = $transitTotalRevenue - $transitTotalCost;
        $transitTotalCalls = $transitData->sum('total_calls');
        $transitTotalMinutes = $transitData->sum('minutes');

        return view('admin.operational-reports.profit-loss', compact(
            'data',
            'dateFrom',
            'dateTo',
            'totalRevenue',
            'totalResellerCost',
            'totalTrunkCost',
            'totalPlatformProfit',
            'totalProfit',
            'avgMargin',
            'totalCalls',
            'totalAnswered',
            'totalMinutes',
            'chartLabels',
            'chartData',
            'transitData',
            'transitTotalRevenue',
            'transitTotalCost',
            'transitTotalProfit',
            'transitTotalCalls',
            'transitTotalMinutes'
        ));
    }

    /**
     * Export Profit & Loss as XLSX.
     */
    public function exportProfitLoss(Request $request)
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::now()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            $dateFrom = $dateTo->copy()->startOfDay();
        }

        if ($dateFrom->diffInDays($dateTo) > 365) {
            $dateTo = $dateFrom->copy()->addDays(365)->endOfDay();
        }

        // Use cdr_summary_daily for fast P&L export
        $data = DB::table('cdr_summary_daily')
            ->whereBetween('cdr_summary_daily.date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->whereNotNull('cdr_summary_daily.reseller_id')
            ->join('users', 'users.id', '=', 'cdr_summary_daily.reseller_id')
            ->groupBy('cdr_summary_daily.reseller_id', 'users.name')
            ->selectRaw('
                cdr_summary_daily.reseller_id,
                users.name as reseller_name,
                SUM(cdr_summary_daily.total_calls) as total_calls,
                SUM(cdr_summary_daily.answered_calls) as answered_calls,
                SUM(cdr_summary_daily.total_billable) as total_billable,
                SUM(cdr_summary_daily.total_cost) as client_revenue,
                SUM(cdr_summary_daily.total_reseller_cost) as reseller_cost
            ')
            ->orderByDesc('client_revenue')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Profit & Loss');

        $headers = ['#', 'Reseller', 'Total Calls', 'Answered', 'ASR%', 'Minutes', 'Client Revenue', 'Reseller Cost', 'Profit', 'Margin %'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(chr(65 + $col) . '1', $header);
        }
        $lastCol = chr(64 + count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $row = 2;
        $totalRevenue = 0;
        $totalResellerCost = 0;
        $totalCalls = 0;
        $totalAnswered = 0;
        $totalMinutes = 0;

        foreach ($data as $i => $r) {
            $minutes = round($r->total_billable / 60, 2);
            $profit = $r->client_revenue - $r->reseller_cost;
            $margin = $r->client_revenue > 0 ? round(($profit / $r->client_revenue) * 100, 1) : 0;
            $asr = $r->total_calls > 0 ? round(($r->answered_calls / $r->total_calls) * 100, 1) : 0;

            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $r->reseller_name);
            $sheet->setCellValue("C{$row}", $r->total_calls);
            $sheet->setCellValue("D{$row}", $r->answered_calls);
            $sheet->setCellValue("E{$row}", $asr . '%');
            $sheet->setCellValue("F{$row}", number_format($minutes, 2));
            $sheet->setCellValue("G{$row}", number_format((float) $r->client_revenue, 4));
            $sheet->setCellValue("H{$row}", number_format((float) $r->reseller_cost, 4));
            $sheet->setCellValue("I{$row}", number_format($profit, 4));
            $sheet->setCellValue("J{$row}", $margin . '%');

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2FF');
            }

            $totalRevenue += $r->client_revenue;
            $totalResellerCost += $r->reseller_cost;
            $totalCalls += $r->total_calls;
            $totalAnswered += $r->answered_calls;
            $totalMinutes += $minutes;
            $row++;
        }

        // Totals row
        $totalProfit = $totalRevenue - $totalResellerCost;
        $totalMargin = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0;
        $sheet->setCellValue("A{$row}", '');
        $sheet->setCellValue("B{$row}", 'TOTAL');
        $sheet->setCellValue("C{$row}", $totalCalls);
        $sheet->setCellValue("D{$row}", $totalAnswered);
        $sheet->setCellValue("E{$row}", '');
        $sheet->setCellValue("F{$row}", number_format($totalMinutes, 2));
        $sheet->setCellValue("G{$row}", number_format($totalRevenue, 4));
        $sheet->setCellValue("H{$row}", number_format($totalResellerCost, 4));
        $sheet->setCellValue("I{$row}", number_format($totalProfit, 4));
        $sheet->setCellValue("J{$row}", $totalMargin . '%');
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);

        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'profit-loss-' . $dateFrom->format('Y-m-d') . '-to-' . $dateTo->format('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Get reseller and trunk lists for filter dropdowns.
     */
    private function getFilterOptions(User $authUser): array
    {
        if ($authUser->isSuperAdmin()) {
            $resellers = User::where('role', 'reseller')->orderBy('name')->get(['id', 'name']);
        } else {
            $resellers = User::where('role', 'reseller')
                ->whereIn('id', $authUser->managedResellerIds())
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        $trunks = Trunk::orderBy('name')->get(['id', 'name']);

        return [$resellers, $trunks];
    }
}

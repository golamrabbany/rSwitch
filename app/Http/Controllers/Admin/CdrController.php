<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ExportCdrJob;
use App\Models\CallRecord;
use App\Models\Trunk;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class CdrController extends Controller
{
    public function index(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        // Build query — call_start ALWAYS first for partition pruning
        $query = CallRecord::query()
            ->whereBetween('call_start', [$dateFrom, $dateTo]);

        $this->applyFilters($query, $request);

        $records = $query->with(['user:id,name', 'sipAccount:id,username'])
            ->orderByDesc('call_start')
            ->paginate(50);

        $stats = $this->getStats($request, $dateFrom, $dateTo);

        $users = User::whereIn('role', ['reseller', 'client'])->select('id', 'name', 'email', 'role')->orderBy('name')->get();
        $trunks = Trunk::select('id', 'name', 'direction')->orderBy('name')->get();

        return view('admin.cdr.index', compact(
            'records', 'stats', 'users', 'trunks', 'dateFrom', 'dateTo'
        ));
    }

    public function show(Request $request, string $uuid)
    {
        // UUID lookup with date param for partition pruning
        if ($request->filled('date')) {
            $date = Carbon::parse($request->date);
            $record = CallRecord::where('uuid', $uuid)
                ->whereBetween('call_start', [
                    $date->copy()->startOfDay(),
                    $date->copy()->endOfDay(),
                ])
                ->firstOrFail();
        } else {
            // Fallback: search current month partition
            $record = CallRecord::where('uuid', $uuid)
                ->whereBetween('call_start', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ])
                ->firstOrFail();
        }

        $record->load([
            'user:id,name,email,role',
            'reseller:id,name,email',
            'sipAccount:id,username,status',
            'incomingTrunk:id,name,provider',
            'outgoingTrunk:id,name,provider',
            'did:id,number',
        ]);

        $hasRecording = file_exists(config('filesystems.disks.recordings.root') . '/' . $record->uuid . '.wav');

        return view('admin.cdr.show', compact('record', 'hasRecording'));
    }

    public function export(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, maxDays: 7);

        if ($dateFrom->diffInDays($dateTo) > 7) {
            return back()->with('warning', 'CSV export is limited to 7 days. Please narrow your date range.');
        }

        $query = CallRecord::query()
            ->whereBetween('call_start', [$dateFrom, $dateTo]);

        $this->applyFilters($query, $request);

        $filename = 'cdr_' . $dateFrom->format('Ymd') . '_' . $dateTo->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'UUID', 'Date/Time', 'Caller', 'Callee', 'Caller ID',
                'Call Flow', 'Disposition', 'Status',
                'Duration (s)', 'Billsec (s)', 'Billable (s)',
                'Rate/Min', 'Connection Fee', 'Total Cost',
                'Destination', 'Matched Prefix',
                'User', 'SIP Account',
                'Incoming Trunk', 'Outgoing Trunk',
                'Src IP', 'Dst IP',
            ]);

            $query->with([
                'user:id,name', 'sipAccount:id,username',
                'incomingTrunk:id,name', 'outgoingTrunk:id,name',
            ])
                ->orderBy('call_start')
                ->chunk(1000, function ($records) use ($handle) {
                    foreach ($records as $r) {
                        fputcsv($handle, [
                            $r->uuid,
                            $r->call_start?->format('Y-m-d H:i:s'),
                            $r->caller,
                            $r->callee,
                            $r->caller_id,
                            $r->call_flow,
                            $r->disposition,
                            $r->status,
                            $r->duration,
                            $r->billsec,
                            $r->billable_duration,
                            $r->rate_per_minute,
                            $r->connection_fee,
                            $r->total_cost,
                            $r->destination,
                            $r->matched_prefix,
                            $r->user?->name,
                            $r->sipAccount?->username,
                            $r->incomingTrunk?->name,
                            $r->outgoingTrunk?->name,
                            $r->src_ip,
                            $r->dst_ip,
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function resolveDateRange(Request $request, int $maxDays = 7): array
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::today()->startOfDay();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::today()->endOfDay();

        // Ensure from <= to
        if ($dateFrom->gt($dateTo)) {
            $dateFrom = $dateTo->copy()->startOfDay();
        }

        // Clamp to max range
        if ($dateFrom->diffInDays($dateTo) > $maxDays) {
            $dateTo = $dateFrom->copy()->addDays($maxDays)->endOfDay();
        }

        return [$dateFrom, $dateTo];
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('disposition')) {
            $query->where('disposition', $request->disposition);
        }

        if ($request->filled('call_flow')) {
            $query->where('call_flow', $request->call_flow);
        }

        if ($request->filled('call_type')) {
            $query->where('call_type', $request->call_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('trunk_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('incoming_trunk_id', $request->trunk_id)
                  ->orWhere('outgoing_trunk_id', $request->trunk_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller', 'like', "{$search}%")
                  ->orWhere('callee', 'like', "{$search}%");
            });
        }
    }

    private function getStats(Request $request, Carbon $dateFrom, Carbon $dateTo): array
    {
        // Use summary table when no complex filters are applied
        $canUseSummary = !$request->filled('search')
            && !$request->filled('trunk_id')
            && !$request->filled('call_flow')
            && !$request->filled('disposition')
            && !$request->filled('status');

        if ($canUseSummary) {
            $summaryQuery = DB::table('cdr_summary_daily')
                ->whereBetween('date', [$dateFrom->toDateString(), $dateTo->toDateString()]);

            if ($request->filled('user_id')) {
                $summaryQuery->where('user_id', $request->user_id);
            }

            $row = $summaryQuery->selectRaw('
                COALESCE(SUM(total_calls), 0) as total_calls,
                COALESCE(SUM(answered_calls), 0) as answered_calls,
                COALESCE(SUM(total_duration), 0) as total_duration,
                COALESCE(SUM(total_billable), 0) as total_billable,
                COALESCE(SUM(total_cost), 0) as total_cost
            ')->first();

            return (array) $row;
        }

        // Fallback: aggregate on call_records with partition pruning
        $statsQuery = CallRecord::query()
            ->whereBetween('call_start', [$dateFrom, $dateTo]);

        $this->applyFilters($statsQuery, $request);

        $row = $statsQuery->selectRaw('
            COUNT(*) as total_calls,
            SUM(disposition = "ANSWERED") as answered_calls,
            COALESCE(SUM(duration), 0) as total_duration,
            COALESCE(SUM(billable_duration), 0) as total_billable,
            COALESCE(SUM(total_cost), 0) as total_cost
        ')->first();

        return [
            'total_calls' => $row->total_calls ?? 0,
            'answered_calls' => $row->answered_calls ?? 0,
            'total_duration' => $row->total_duration ?? 0,
            'total_billable' => $row->total_billable ?? 0,
            'total_cost' => $row->total_cost ?? 0,
        ];
    }

    /**
     * Queue a monthly CDR export as a background job.
     */
    public function exportMonthly(Request $request)
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'user_id' => ['nullable', 'exists:users,id'],
            'reseller_id' => ['nullable', 'exists:users,id'],
        ]);

        ExportCdrJob::dispatch(
            requestedBy: auth()->id(),
            month: $validated['month'],
            userId: $validated['user_id'] ?? null,
            resellerId: $validated['reseller_id'] ?? null,
        );

        return back()->with('success', "CDR export for {$validated['month']} queued. You'll find the file in storage/exports/ when ready.");
    }

    /**
     * List available CDR export files for download.
     */
    public function exportFiles()
    {
        $files = collect(Storage::files('exports'))
            ->filter(fn($f) => str_ends_with($f, '.csv.gz'))
            ->map(fn($f) => [
                'name' => basename($f),
                'path' => $f,
                'size' => round(Storage::size($f) / 1024 / 1024, 2),
                'date' => Carbon::createFromTimestamp(Storage::lastModified($f)),
            ])
            ->sortByDesc('date')
            ->values();

        return view('admin.cdr.exports', compact('files'));
    }

    /**
     * Download an export file.
     */
    public function downloadExport(string $filename)
    {
        $path = 'exports/' . basename($filename);

        if (!Storage::exists($path)) {
            return back()->with('error', 'Export file not found.');
        }

        return Storage::download($path, basename($filename), [
            'Content-Type' => 'application/gzip',
        ]);
    }

    /**
     * Trigger restore of archived CDR month via Python Celery task.
     */
    public function restoreArchive(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        [$year, $month] = explode('-', $validated['month']);

        // Check if already restoring/restored
        $key = "rswitch:restored_archive:{$validated['month']}";
        if (Redis::exists($key)) {
            return back()->with('info', "Archive for {$validated['month']} is already restored (available for 24h).");
        }

        // Check if archive files exist
        $archiveDir = '/var/backups/rswitch/cdr';
        $pattern = "cdr-{$validated['month']}-*.csv.gz";
        $files = glob("{$archiveDir}/{$pattern}");

        if (empty($files)) {
            return back()->with('error', "No archive files found for {$validated['month']}.");
        }

        // Trigger Celery task via Python API
        try {
            $pythonApiUrl = config('services.python_api.url', 'http://127.0.0.1:8001');
            Http::post("{$pythonApiUrl}/tasks/restore-cdr-archive", [
                'year' => (int) $year,
                'month' => (int) $month,
            ]);
        } catch (\Exception $e) {
            // Fallback: dispatch via Redis directly
            Redis::rpush('celery', json_encode([
                'id' => \Str::uuid()->toString(),
                'task' => 'billing.tasks.restore_cdr_archive',
                'args' => [(int) $year, (int) $month],
                'kwargs' => [],
            ]));
        }

        return back()->with('success', "Restoring CDR archive for {$validated['month']}. This may take 5-10 minutes. Data will be available for 24 hours.");
    }

    /**
     * Check available archive months for restore.
     */
    public function availableArchives()
    {
        $archiveDir = '/var/backups/rswitch/cdr';
        $months = [];

        if (is_dir($archiveDir)) {
            $files = glob("{$archiveDir}/cdr-*.csv.gz");
            foreach ($files as $file) {
                // Extract month from filename: cdr-2025-07-15.csv.gz → 2025-07
                if (preg_match('/cdr-(\d{4}-\d{2})-\d{2}\.csv\.gz/', basename($file), $m)) {
                    $months[$m[1]] = ($months[$m[1]] ?? 0) + 1;
                }
            }
        }

        ksort($months);

        // Check which are currently restored
        $restored = [];
        foreach ($months as $month => $dayCount) {
            $key = "rswitch:restored_archive:{$month}";
            $restored[$month] = Redis::exists($key) ? true : false;
        }

        return response()->json([
            'months' => $months,
            'restored' => $restored,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rate;
use App\Models\RateGroup;
use App\Models\RateImport;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\Rule;

class RateGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = RateGroup::withCount(['rates', 'users'])
            ->with('creator:id,name');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $rateGroups = $query->orderBy('name')->paginate(20);

        return view('admin.rate-groups.index', compact('rateGroups'));
    }

    public function create()
    {
        $adminGroups = RateGroup::where('type', 'admin')->orderBy('name')->get(['id', 'name']);

        return view('admin.rate-groups.create', compact('adminGroups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'type' => ['required', Rule::in(['admin', 'reseller'])],
            'parent_rate_group_id' => [
                'nullable',
                'required_if:type,reseller',
                'exists:rate_groups,id',
            ],
        ]);

        if ($validated['type'] === 'admin') {
            $validated['parent_rate_group_id'] = null;
        }

        $validated['created_by'] = auth()->id();

        $rateGroup = RateGroup::create($validated);

        AuditService::logCreated($rateGroup);

        return redirect()->route('admin.rate-groups.show', $rateGroup)
            ->with('success', "Rate group \"{$rateGroup->name}\" created.");
    }

    public function show(Request $request, RateGroup $rateGroup)
    {
        $rateGroup->loadCount(['rates', 'users']);
        $rateGroup->load('creator:id,name', 'parentRateGroup:id,name');

        $ratesQuery = $rateGroup->rates();

        if ($request->filled('prefix')) {
            $ratesQuery->where('prefix', 'like', "{$request->prefix}%");
        }

        if ($request->filled('destination')) {
            $ratesQuery->where('destination', 'like', "%{$request->destination}%");
        }

        if ($request->filled('status')) {
            $ratesQuery->where('status', $request->status);
        }

        if ($request->filled('rate_type')) {
            $ratesQuery->where('rate_type', $request->rate_type);
        }

        $rates = $ratesQuery->orderBy('prefix')->paginate(50);

        $recentImports = $rateGroup->rateImports()
            ->with('uploader:id,name')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.rate-groups.show', compact('rateGroup', 'rates', 'recentImports'));
    }

    public function edit(RateGroup $rateGroup)
    {
        $adminGroups = RateGroup::where('type', 'admin')
            ->where('id', '!=', $rateGroup->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.rate-groups.edit', compact('rateGroup', 'adminGroups'));
    }

    public function update(Request $request, RateGroup $rateGroup)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'type' => ['required', Rule::in(['admin', 'reseller'])],
            'parent_rate_group_id' => [
                'nullable',
                'required_if:type,reseller',
                'exists:rate_groups,id',
            ],
        ]);

        if ($validated['type'] === 'admin') {
            $validated['parent_rate_group_id'] = null;
        }

        $original = $rateGroup->getAttributes();
        $rateGroup->update($validated);

        AuditService::logUpdated($rateGroup, $original);

        return redirect()->route('admin.rate-groups.show', $rateGroup)
            ->with('success', "Rate group \"{$rateGroup->name}\" updated.");
    }

    public function destroy(RateGroup $rateGroup)
    {
        if ($rateGroup->users()->exists()) {
            return back()->withErrors(['delete' => 'Cannot delete a rate group that has users assigned to it.']);
        }

        AuditService::logAction('deleted', $rateGroup, $rateGroup->toArray());
        $rateGroup->delete();

        return redirect()->route('admin.rate-groups.index')
            ->with('success', "Rate group deleted.");
    }

    public function exportCsv(RateGroup $rateGroup)
    {
        $filename = 'rates_' . str_replace(' ', '_', strtolower($rateGroup->name)) . '_' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rateGroup) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'prefix', 'destination', 'rate_per_minute', 'connection_fee',
                'min_duration', 'billing_increment', 'effective_date', 'end_date', 'status', 'rate_type',
            ]);

            $rateGroup->rates()
                ->orderBy('prefix')
                ->chunk(1000, function ($rates) use ($handle) {
                    foreach ($rates as $rate) {
                        fputcsv($handle, [
                            $rate->prefix,
                            $rate->destination,
                            $rate->rate_per_minute,
                            $rate->connection_fee,
                            $rate->min_duration,
                            $rate->billing_increment,
                            $rate->effective_date?->format('Y-m-d'),
                            $rate->end_date?->format('Y-m-d'),
                            $rate->status,
                            $rate->rate_type ?? 'regular',
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importCsv(Request $request, RateGroup $rateGroup)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'mode' => ['required', Rule::in(['merge', 'replace', 'add_only'])],
            'effective_date' => 'required|date',
        ]);

        $file = $request->file('file');
        $mode = $request->mode;
        $effectiveDate = $request->effective_date;

        // Store file for audit
        $storedPath = $file->store('rate-imports', 'local');

        $import = RateImport::create([
            'rate_group_id' => $rateGroup->id,
            'uploaded_by' => auth()->id(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'effective_date' => $effectiveDate,
            'status' => 'processing',
        ]);

        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (!$header) {
            $import->update(['status' => 'failed', 'error_log' => ['Empty CSV file']]);
            fclose($handle);
            return back()->withErrors(['file' => 'The CSV file is empty.']);
        }

        // Normalize header names
        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        $requiredColumns = ['prefix', 'destination', 'rate_per_minute'];
        $missing = array_diff($requiredColumns, $header);
        if (!empty($missing)) {
            $import->update([
                'status' => 'failed',
                'error_log' => ['Missing required columns: ' . implode(', ', $missing)],
            ]);
            fclose($handle);
            return back()->withErrors(['file' => 'Missing required columns: ' . implode(', ', $missing)]);
        }

        // If REPLACE mode, delete all existing rates first
        if ($mode === 'replace') {
            $rateGroup->rates()->delete();
        }

        // Build prefix index for merge/add_only modes
        $existingPrefixes = [];
        if ($mode !== 'replace') {
            $existingPrefixes = $rateGroup->rates()->pluck('prefix', 'prefix')->all();
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (count($row) < count($header)) {
                $errors[] = "Row {$rowNum}: insufficient columns";
                continue;
            }

            $data = array_combine($header, $row);
            $prefix = trim($data['prefix'] ?? '');
            $destination = trim($data['destination'] ?? '');
            $ratePerMinute = trim($data['rate_per_minute'] ?? '');

            // Validate
            if (!preg_match('/^\d{1,20}$/', $prefix)) {
                $errors[] = "Row {$rowNum}: invalid prefix '{$prefix}'";
                continue;
            }

            if (empty($destination)) {
                $errors[] = "Row {$rowNum}: missing destination";
                continue;
            }

            if (!is_numeric($ratePerMinute) || $ratePerMinute < 0) {
                $errors[] = "Row {$rowNum}: invalid rate_per_minute '{$ratePerMinute}'";
                continue;
            }

            $rateData = [
                'rate_group_id' => $rateGroup->id,
                'prefix' => $prefix,
                'destination' => $destination,
                'rate_per_minute' => $ratePerMinute,
                'connection_fee' => is_numeric($data['connection_fee'] ?? '') ? $data['connection_fee'] : 0,
                'min_duration' => is_numeric($data['min_duration'] ?? '') ? (int) $data['min_duration'] : 0,
                'billing_increment' => is_numeric($data['billing_increment'] ?? '') ? max(1, (int) $data['billing_increment']) : 6,
                'effective_date' => $effectiveDate,
                'end_date' => !empty($data['end_date'] ?? '') ? $data['end_date'] : null,
                'status' => in_array($data['status'] ?? '', ['active', 'disabled']) ? $data['status'] : 'active',
                'rate_type' => in_array($data['rate_type'] ?? '', ['regular', 'broadcast']) ? $data['rate_type'] : 'regular',
            ];

            if ($mode === 'add_only' && isset($existingPrefixes[$prefix])) {
                $skipped++;
                continue;
            }

            if ($mode === 'merge' && isset($existingPrefixes[$prefix])) {
                $rateGroup->rates()->where('prefix', $prefix)->update($rateData);
                $imported++;
                continue;
            }

            Rate::create($rateData);
            $existingPrefixes[$prefix] = $prefix;
            $imported++;
        }

        fclose($handle);

        $import->update([
            'total_rows' => $rowNum - 1,
            'imported_rows' => $imported,
            'skipped_rows' => $skipped,
            'error_rows' => count($errors),
            'error_log' => !empty($errors) ? array_slice($errors, 0, 100) : null,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Notify Python billing service to clear rate cache after import
        Redis::publish('rswitch:rate.updated', json_encode(['rate_group_id' => $rateGroup->id]));

        $message = "Import completed: {$imported} imported, {$skipped} skipped, " . count($errors) . " errors.";

        return redirect()->route('admin.rate-groups.show', $rateGroup)
            ->with('success', $message);
    }
}

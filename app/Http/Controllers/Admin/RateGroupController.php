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

    public function export(RateGroup $rateGroup)
    {
        $filename = 'rates_' . str_replace(' ', '_', strtolower($rateGroup->name)) . '_' . now()->format('Ymd') . '.xlsx';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rates');

        $headers = ['Prefix', 'Destination', 'Rate/Min', 'Connection Fee', 'Min Duration', 'Billing Increment', 'Effective Date', 'End Date', 'Status', 'Rate Type'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(chr(65 + $col) . '1', $header);
        }

        // Style header
        $lastCol = chr(64 + count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        $row = 2;
        $rateGroup->rates()->orderBy('prefix')->chunk(1000, function ($rates) use ($sheet, &$row) {
            foreach ($rates as $rate) {
                $sheet->setCellValueExplicit("A{$row}", $rate->prefix, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("B{$row}", $rate->destination);
                $sheet->setCellValue("C{$row}", (float) $rate->rate_per_minute);
                $sheet->setCellValue("D{$row}", (float) $rate->connection_fee);
                $sheet->setCellValue("E{$row}", (int) $rate->min_duration);
                $sheet->setCellValue("F{$row}", (int) $rate->billing_increment);
                $sheet->setCellValue("G{$row}", $rate->effective_date?->format('Y-m-d'));
                $sheet->setCellValue("H{$row}", $rate->end_date?->format('Y-m-d'));
                $sheet->setCellValue("I{$row}", $rate->status);
                $sheet->setCellValue("J{$row}", $rate->rate_type ?? 'regular');
                $row++;
            }
        });

        // Auto-width columns
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function import(Request $request, RateGroup $rateGroup)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
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

        // Read file using PhpSpreadsheet (supports xlsx, xls, csv)
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
        } catch (\Exception $e) {
            $import->update(['status' => 'failed', 'error_log' => ['Cannot read file: ' . $e->getMessage()]]);
            return back()->withErrors(['file' => 'Cannot read file. Please upload a valid XLSX or CSV file.']);
        }

        if (empty($rows)) {
            $import->update(['status' => 'failed', 'error_log' => ['Empty file']]);
            return back()->withErrors(['file' => 'The file is empty.']);
        }

        // First row is header
        $headerRow = array_shift($rows);
        $header = array_map(fn($h) => strtolower(trim($h ?? '')), array_values($headerRow));

        $requiredColumns = ['prefix', 'destination', 'rate_per_minute'];
        // Also accept 'rate/min' as alias
        $normalizedHeader = array_map(fn($h) => str_replace(['rate/min', 'rate per minute'], 'rate_per_minute', str_replace(' ', '_', $h)), $header);
        $missing = array_diff($requiredColumns, $normalizedHeader);
        if (!empty($missing)) {
            $import->update([
                'status' => 'failed',
                'error_log' => ['Missing required columns: ' . implode(', ', $missing)],
            ]);
            return back()->withErrors(['file' => 'Missing required columns: ' . implode(', ', $missing)]);
        }
        $header = $normalizedHeader;

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

        foreach ($rows as $rowValues) {
            $rowNum++;
            $row = array_values($rowValues);

            if (count($row) < count($header)) {
                $errors[] = "Row {$rowNum}: insufficient columns";
                continue;
            }

            $data = array_combine($header, $row);
            $prefix = trim($data['prefix'] ?? '');
            $destination = trim($data['destination'] ?? '');
            $ratePerMinute = trim($data['rate_per_minute'] ?? '');

            // Skip empty rows
            if (empty($prefix) && empty($destination)) {
                continue;
            }

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

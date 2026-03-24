<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Rate;
use App\Models\RateGroup;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class TariffController extends Controller
{
    /**
     * Show the base tariff assigned by admin (read-only).
     */
    public function baseTariff(Request $request)
    {
        $user = auth()->user();
        $baseTariff = $user->rateGroup;

        if (!$baseTariff) {
            return view('reseller.tariffs.base-tariff', ['baseTariff' => null, 'rates' => collect()]);
        }

        $query = $baseTariff->rates()->where('status', 'active');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('prefix', 'like', "{$search}%")
                  ->orWhere('destination', 'like', "%{$search}%");
            });
        }

        $rates = $query->orderBy('prefix')->paginate(25);

        return view('reseller.tariffs.base-tariff', compact('baseTariff', 'rates'));
    }

    /**
     * Export base tariff rates as XLSX.
     */
    public function exportBaseTariff()
    {
        $user = auth()->user();
        $baseTariff = $user->rateGroup;
        abort_unless($baseTariff, 403, 'No base tariff assigned.');

        $rates = $baseTariff->rates()->where('status', 'active')->orderBy('prefix')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Base Tariff');

        // Header row
        $headers = ['Prefix', 'Destination', 'Rate/Minute', 'Connection Fee', 'Min Duration (s)', 'Billing Increment (s)', 'Effective Date'];
        foreach ($headers as $col => $header) {
            $cell = chr(65 + $col) . '1';
            $sheet->setCellValue($cell, $header);
        }

        // Style header
        $headerRange = 'A1:G1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Data rows
        $row = 2;
        foreach ($rates as $rate) {
            $sheet->setCellValue("A{$row}", $rate->prefix);
            $sheet->setCellValue("B{$row}", $rate->destination);
            $sheet->setCellValue("C{$row}", (float) $rate->rate_per_minute);
            $sheet->setCellValue("D{$row}", (float) $rate->connection_fee);
            $sheet->setCellValue("E{$row}", $rate->min_duration);
            $sheet->setCellValue("F{$row}", $rate->billing_increment);
            $sheet->setCellValue("G{$row}", $rate->effective_date);

            // Alternate row color
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0FDF4');
            }
            $row++;
        }

        // Auto-width columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Format rate columns as number
        $sheet->getStyle('C2:D' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.000000');

        $filename = 'base-tariff-' . now()->format('Y-m-d') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * List reseller's tariffs: base tariff + own created tariffs.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = RateGroup::where('created_by', $user->id)
            ->where('type', 'reseller')
            ->withCount(['rates', 'users']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $rateGroups = $query->latest()->paginate(20);

        return view('reseller.tariffs.index', compact('rateGroups'));
    }

    /**
     * Show tariff details with rates.
     */
    public function show(Request $request, RateGroup $tariff)
    {
        $user = auth()->user();

        abort_unless(
            $tariff->id === $user->rate_group_id ||
            ($tariff->created_by === $user->id && $tariff->type === 'reseller'),
            403
        );

        $query = $tariff->rates();

        // Base tariff: only show active. Own tariff: show all (including disabled)
        if ($tariff->id === $user->rate_group_id) {
            $query->where('status', 'active');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('prefix', 'like', "{$search}%")
                  ->orWhere('destination', 'like', "%{$search}%");
            });
        }

        $rates = $query->orderBy('status')->orderBy('prefix')->paginate(25);

        $isBaseTariff = $tariff->id === $user->rate_group_id;

        return view('reseller.tariffs.show', compact('tariff', 'rates', 'isBaseTariff'));
    }

    /**
     * Show create tariff form.
     */
    public function create()
    {
        $baseTariff = auth()->user()->rateGroup;
        abort_unless($baseTariff, 403, 'No base tariff assigned. Contact admin.');

        return view('reseller.tariffs.create', compact('baseTariff'));
    }

    /**
     * Store a new reseller tariff.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $baseTariff = $user->rateGroup;
        abort_unless($baseTariff, 403, 'No base tariff assigned.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'copy_rates' => ['nullable', 'boolean'],
            'markup_percent' => ['nullable', 'numeric', 'min:0', 'max:500'],
        ]);

        $tariff = RateGroup::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => 'reseller',
            'parent_rate_group_id' => $baseTariff->id,
            'created_by' => $user->id,
        ]);

        // Optionally copy rates from base tariff with markup
        if ($request->boolean('copy_rates')) {
            $markup = ($validated['markup_percent'] ?? 0) / 100;

            $baseRates = $baseTariff->rates()->where('status', 'active')->get();
            foreach ($baseRates as $rate) {
                Rate::create([
                    'rate_group_id' => $tariff->id,
                    'prefix' => $rate->prefix,
                    'destination' => $rate->destination,
                    'rate_per_minute' => round($rate->rate_per_minute * (1 + $markup), 6),
                    'connection_fee' => $rate->connection_fee,
                    'min_duration' => $rate->min_duration,
                    'billing_increment' => $rate->billing_increment,
                    'effective_date' => now()->toDateString(),
                    'status' => 'active',
                ]);
            }
        }

        AuditService::logCreated($tariff, 'reseller.tariff.created');

        return redirect()->route('reseller.tariffs.show', $tariff)
            ->with('success', "Tariff \"{$tariff->name}\" created with " . ($request->boolean('copy_rates') ? $baseTariff->rates()->count() . ' rates copied.' : 'no rates. Add rates manually.'));
    }

    /**
     * Edit tariff.
     */
    public function edit(RateGroup $tariff)
    {
        abort_unless($tariff->created_by === auth()->id() && $tariff->type === 'reseller', 403);

        return view('reseller.tariffs.edit', compact('tariff'));
    }

    /**
     * Update tariff name/description.
     */
    public function update(Request $request, RateGroup $tariff)
    {
        abort_unless($tariff->created_by === auth()->id() && $tariff->type === 'reseller', 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $original = $tariff->getAttributes();
        $tariff->update($validated);

        AuditService::logUpdated($tariff, $original, 'reseller.tariff.updated');

        return redirect()->route('reseller.tariffs.show', $tariff)
            ->with('success', 'Tariff updated.');
    }

    /**
     * Delete tariff (only if no users assigned).
     */
    public function destroy(RateGroup $tariff)
    {
        abort_unless($tariff->created_by === auth()->id() && $tariff->type === 'reseller', 403);

        if ($tariff->users()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete tariff — clients are still assigned to it.']);
        }

        AuditService::logAction('reseller.tariff.deleted', $tariff, $tariff->toArray());
        $tariff->delete();

        return redirect()->route('reseller.tariffs.index')
            ->with('success', 'Tariff deleted.');
    }

    /**
     * Export tariff rates as XLSX.
     */
    public function exportTariff(RateGroup $tariff)
    {
        abort_unless($tariff->created_by === auth()->id() && $tariff->type === 'reseller', 403);

        $rates = $tariff->rates()->where('status', 'active')->orderBy('prefix')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($tariff->name);

        $headers = ['Prefix', 'Destination', 'Rate/Minute', 'Connection Fee', 'Min Duration (s)', 'Billing Increment (s)', 'Effective Date'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(chr(65 + $col) . '1', $header);
        }

        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $row = 2;
        foreach ($rates as $rate) {
            $sheet->setCellValue("A{$row}", $rate->prefix);
            $sheet->setCellValue("B{$row}", $rate->destination);
            $sheet->setCellValue("C{$row}", (float) $rate->rate_per_minute);
            $sheet->setCellValue("D{$row}", (float) $rate->connection_fee);
            $sheet->setCellValue("E{$row}", $rate->min_duration);
            $sheet->setCellValue("F{$row}", $rate->billing_increment);
            $sheet->setCellValue("G{$row}", $rate->effective_date);
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0FDF4');
            }
            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('C2:D' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.000000');

        $filename = Str::slug($tariff->name) . '-rates-' . now()->format('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import rates from XLSX file.
     */
    public function importTariff(Request $request, RateGroup $tariff)
    {
        abort_unless($tariff->created_by === auth()->id() && $tariff->type === 'reseller', 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
            'mode' => ['required', 'in:merge,add_only,replace'],
        ]);

        $mode = $request->input('mode');
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($request->file('file')->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            return back()->withErrors(['file' => 'File has no data rows.']);
        }

        // Parse header row
        $header = array_map('strtolower', array_map('trim', $rows[1]));
        $colMap = array_flip($header);

        if (!isset($colMap['prefix']) || !isset($colMap['rate_per_minute'])) {
            return back()->withErrors(['file' => 'Missing required columns: prefix, rate_per_minute']);
        }

        // Replace mode: disable all existing rates first
        if ($mode === 'replace') {
            $tariff->rates()->update(['status' => 'disabled']);
        }

        $existing = $tariff->rates()->where('status', 'active')->pluck('prefix', 'prefix')->toArray();
        $imported = 0;
        $skipped = 0;

        for ($i = 2; $i <= count($rows); $i++) {
            $row = $rows[$i] ?? null;
            if (!$row) continue;

            $prefix = trim($row[$this->colLetter($colMap, 'prefix')] ?? '');
            if (!$prefix || !preg_match('/^\d{1,20}$/', $prefix)) {
                $skipped++;
                continue;
            }

            $destination = trim($row[$this->colLetter($colMap, 'destination')] ?? $prefix);
            $ratePerMin = floatval($row[$this->colLetter($colMap, 'rate_per_minute')] ?? 0);
            $connFee = floatval($row[$this->colLetter($colMap, 'connection_fee')] ?? 0);
            $minDur = intval($row[$this->colLetter($colMap, 'min_duration')] ?? 0);
            $increment = intval($row[$this->colLetter($colMap, 'billing_increment')] ?? 6);

            if ($mode === 'add_only' && isset($existing[$prefix])) {
                $skipped++;
                continue;
            }

            if ($mode === 'merge' && isset($existing[$prefix])) {
                $tariff->rates()->where('prefix', $prefix)->where('status', 'active')->update([
                    'destination' => $destination,
                    'rate_per_minute' => $ratePerMin,
                    'connection_fee' => $connFee,
                    'min_duration' => $minDur,
                    'billing_increment' => max(1, $increment),
                ]);
                $imported++;
                continue;
            }

            Rate::create([
                'rate_group_id' => $tariff->id,
                'prefix' => $prefix,
                'destination' => $destination,
                'rate_per_minute' => $ratePerMin,
                'connection_fee' => $connFee,
                'min_duration' => $minDur,
                'billing_increment' => max(1, $increment),
                'effective_date' => now()->toDateString(),
                'status' => 'active',
            ]);
            $existing[$prefix] = $prefix;
            $imported++;
        }

        Redis::publish('rswitch:rate.updated', json_encode(['rate_group_id' => $tariff->id]));

        return redirect()->route('reseller.tariffs.show', $tariff)
            ->with('success', "Import completed: {$imported} imported, {$skipped} skipped.");
    }

    private function colLetter(array $colMap, string $name): string
    {
        if (!isset($colMap[$name])) return 'A';
        $index = $colMap[$name];
        return chr(65 + $index);
    }

    /**
     * Show add rate form.
     */
    public function createRate(RateGroup $tariff)
    {
        abort_unless($tariff->created_by === auth()->id() && $tariff->type === 'reseller', 403);

        return view('reseller.tariffs.rate-form', [
            'tariff' => $tariff,
            'rate' => null,
        ]);
    }

    /**
     * Show edit rate form.
     */
    public function editRate(RateGroup $tariff, Rate $rate)
    {
        abort_unless($tariff->created_by === auth()->id() && $tariff->type === 'reseller', 403);
        abort_unless($rate->rate_group_id === $tariff->id, 403);

        return view('reseller.tariffs.rate-form', compact('tariff', 'rate'));
    }

    /**
     * Update a rate.
     */
    public function updateRate(Request $request, RateGroup $tariff, Rate $rate)
    {
        abort_unless($tariff->created_by === auth()->id() && $tariff->type === 'reseller', 403);
        abort_unless($rate->rate_group_id === $tariff->id, 403);

        $validated = $request->validate([
            'prefix' => ['required', 'string', 'regex:/^\d{1,20}$/'],
            'destination' => ['required', 'string', 'max:100'],
            'rate_per_minute' => ['required', 'numeric', 'min:0'],
            'connection_fee' => ['nullable', 'numeric', 'min:0'],
            'min_duration' => ['nullable', 'integer', 'min:0'],
            'billing_increment' => ['nullable', 'integer', 'min:1'],
        ]);

        $rate->update([
            'prefix' => $validated['prefix'],
            'destination' => $validated['destination'],
            'rate_per_minute' => $validated['rate_per_minute'],
            'connection_fee' => $validated['connection_fee'] ?? 0,
            'min_duration' => $validated['min_duration'] ?? 0,
            'billing_increment' => $validated['billing_increment'] ?? 6,
        ]);

        Redis::publish('rswitch:rate.updated', json_encode(['rate_group_id' => $tariff->id]));

        return redirect()->route('reseller.tariffs.show', $tariff)
            ->with('success', "Rate for prefix \"{$rate->prefix}\" updated.");
    }

    /**
     * Add a rate to reseller's tariff.
     */
    public function addRate(Request $request, RateGroup $tariff)
    {
        abort_unless($tariff->created_by === auth()->id() && $tariff->type === 'reseller', 403);

        $validated = $request->validate([
            'prefix' => ['required', 'string', 'regex:/^\d{1,20}$/'],
            'destination' => ['required', 'string', 'max:100'],
            'rate_per_minute' => ['required', 'numeric', 'min:0'],
            'connection_fee' => ['nullable', 'numeric', 'min:0'],
            'billing_increment' => ['nullable', 'integer', 'min:1'],
        ]);

        $rate = Rate::create([
            'rate_group_id' => $tariff->id,
            'prefix' => $validated['prefix'],
            'destination' => $validated['destination'],
            'rate_per_minute' => $validated['rate_per_minute'],
            'connection_fee' => $validated['connection_fee'] ?? 0,
            'min_duration' => 0,
            'billing_increment' => $validated['billing_increment'] ?? 6,
            'effective_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        // Notify Python billing service to clear cache
        Redis::publish('rswitch:rate.updated', json_encode(['rate_group_id' => $tariff->id]));

        return back()->with('success', "Rate for prefix \"{$rate->prefix}\" added.");
    }

    /**
     * Soft delete a rate (set status to disabled).
     * Billing engine only uses active rates, so disabled rates are ignored.
     */
    public function deleteRate(RateGroup $tariff, Rate $rate)
    {
        abort_unless($tariff->created_by === auth()->id() && $tariff->type === 'reseller', 403);
        abort_unless($rate->rate_group_id === $tariff->id, 403);

        $rate->update(['status' => 'disabled']);

        Redis::publish('rswitch:rate.updated', json_encode(['rate_group_id' => $tariff->id]));

        return back()->with('success', "Rate for prefix \"{$rate->prefix}\" deleted.");
    }
}

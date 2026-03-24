<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\CallRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReportController extends Controller
{
    /**
     * Active calls (in_progress status).
     */
    public function activeCalls(Request $request)
    {
        $descendantIds = auth()->user()->descendantIds();

        $query = CallRecord::query()
            ->where('status', 'in_progress')
            ->whereIn('user_id', $descendantIds);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller', 'like', "{$search}%")->orWhere('callee', 'like', "{$search}%");
            });
        }

        $calls = $query->with(['user:id,name', 'sipAccount:id,username'])
            ->orderByDesc('call_start')
            ->limit(100)
            ->get();

        return view('reseller.reports.active-calls', compact('calls'));
    }

    /**
     * Answered/Success calls.
     */
    public function successCalls(Request $request)
    {
        $descendantIds = auth()->user()->descendantIds();
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $query = CallRecord::query()
            ->whereBetween('call_start', [$dateFrom, $dateTo])
            ->whereIn('user_id', $descendantIds)
            ->where('disposition', 'ANSWERED');

        if ($request->filled('user_id') && in_array((int) $request->user_id, $descendantIds)) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller', 'like', "{$search}%")->orWhere('callee', 'like', "{$search}%");
            });
        }

        $records = $query->with(['user:id,name', 'sipAccount:id,username'])
            ->orderByDesc('call_start')
            ->paginate(25);

        $stats = $this->getFilteredStats($dateFrom, $dateTo, $descendantIds, 'ANSWERED', false, $request->filled('user_id') ? (int) $request->user_id : null);

        $clients = $this->getClientList($descendantIds);

        return view('reseller.reports.success-calls', compact('records', 'stats', 'dateFrom', 'dateTo', 'clients'));
    }

    /**
     * Failed calls (NO ANSWER, BUSY, FAILED, CANCEL).
     */
    public function failedCalls(Request $request)
    {
        $descendantIds = auth()->user()->descendantIds();
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $query = CallRecord::query()
            ->whereBetween('call_start', [$dateFrom, $dateTo])
            ->whereIn('user_id', $descendantIds)
            ->whereIn('disposition', ['NO ANSWER', 'BUSY', 'FAILED', 'CANCEL']);

        if ($request->filled('user_id') && in_array((int) $request->user_id, $descendantIds)) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller', 'like', "{$search}%")->orWhere('callee', 'like', "{$search}%");
            });
        }

        $records = $query->with(['user:id,name', 'sipAccount:id,username'])
            ->orderByDesc('call_start')
            ->paginate(50);

        $stats = $this->getFilteredStats($dateFrom, $dateTo, $descendantIds, null, true, $request->filled('user_id') ? (int) $request->user_id : null);

        $clients = $this->getClientList($descendantIds);

        return view('reseller.reports.failed-calls', compact('records', 'stats', 'dateFrom', 'dateTo', 'clients'));
    }

    /**
     * Call summary (grouped by date).
     */
    public function callSummary(Request $request)
    {
        $descendantIds = auth()->user()->descendantIds();
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, 30);

        $summaryQuery = DB::table('cdr_summary_daily')
            ->whereBetween('date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->whereIn('user_id', $descendantIds);

        $totalsQuery = DB::table('cdr_summary_daily')
            ->whereBetween('date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->whereIn('user_id', $descendantIds);

        if ($request->filled('user_id') && in_array((int) $request->user_id, $descendantIds)) {
            $summaryQuery->where('user_id', $request->user_id);
            $totalsQuery->where('user_id', $request->user_id);
        }

        $summary = $summaryQuery->selectRaw('
                date,
                SUM(total_calls) as total_calls,
                SUM(answered_calls) as answered_calls,
                SUM(total_calls) - SUM(answered_calls) as failed_calls,
                SUM(total_duration) as total_duration,
                SUM(total_billable) as total_billable,
                SUM(total_cost) as total_cost,
                SUM(total_reseller_cost) as reseller_cost
            ')
            ->groupBy('date')
            ->orderByDesc('date')
            ->paginate(31);

        $totals = $totalsQuery->selectRaw('
                SUM(total_calls) as total_calls,
                SUM(answered_calls) as answered_calls,
                SUM(total_duration) as total_duration,
                SUM(total_billable) as total_billable,
                SUM(total_cost) as total_cost,
                SUM(total_reseller_cost) as reseller_cost
            ')
            ->first();

        $clients = $this->getClientList($descendantIds);

        return view('reseller.reports.call-summary', compact('summary', 'totals', 'dateFrom', 'dateTo', 'clients'));
    }

    /**
     * Export success calls as XLSX.
     */
    public function exportSuccessCalls(Request $request)
    {
        $descendantIds = auth()->user()->descendantIds();
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $query = CallRecord::query()
            ->whereBetween('call_start', [$dateFrom, $dateTo])
            ->whereIn('user_id', $descendantIds)
            ->where('disposition', 'ANSWERED');

        if ($request->filled('user_id') && in_array((int) $request->user_id, $descendantIds)) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller', 'like', "{$search}%")->orWhere('callee', 'like', "{$search}%");
            });
        }

        $records = $query->with(['user:id,name'])->orderByDesc('call_start')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Success Calls');

        $headers = ['Date/Time', 'Caller', 'Client', 'Callee', 'Duration', 'Client Rate', 'My Rate', 'Client Cost', 'My Cost', 'Profit'];
        $this->writeHeaders($sheet, $headers);

        $row = 2;
        foreach ($records as $r) {
            $clientCost = (float) $r->total_cost;
            $resellerCost = (float) $r->reseller_cost;
            $profit = $clientCost - $resellerCost;
            $myRate = $resellerCost > 0 && $r->billable_duration > 0 ? $resellerCost / ($r->billable_duration / 60) : 0;

            $sheet->setCellValue("A{$row}", $r->call_start?->format('Y-m-d H:i:s'));
            $sheet->setCellValue("B{$row}", $r->caller);
            $sheet->setCellValue("C{$row}", $r->user?->name ?? '');
            $sheet->setCellValue("D{$row}", $r->callee);
            $sheet->setCellValue("E{$row}", sprintf('%d:%02d', intdiv($r->billable_duration, 60), $r->billable_duration % 60));
            $sheet->setCellValue("F{$row}", (float) $r->rate_per_minute);
            $sheet->setCellValue("G{$row}", $myRate);
            $sheet->setCellValue("H{$row}", $clientCost);
            $sheet->setCellValue("I{$row}", $resellerCost);
            $sheet->setCellValue("J{$row}", $profit);

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:J{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0FDF4');
            }
            $row++;
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('F2:J' . max($row - 1, 2))->getNumberFormat()->setFormatCode('#,##0.0000');

        $filename = 'success-calls-' . $dateFrom->format('Y-m-d') . '-to-' . $dateTo->format('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Export failed calls as XLSX.
     */
    public function exportFailedCalls(Request $request)
    {
        $descendantIds = auth()->user()->descendantIds();
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $query = CallRecord::query()
            ->whereBetween('call_start', [$dateFrom, $dateTo])
            ->whereIn('user_id', $descendantIds)
            ->whereIn('disposition', ['NO ANSWER', 'BUSY', 'FAILED', 'CANCEL']);

        if ($request->filled('user_id') && in_array((int) $request->user_id, $descendantIds)) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller', 'like', "{$search}%")->orWhere('callee', 'like', "{$search}%");
            });
        }

        $records = $query->with(['user:id,name'])->orderByDesc('call_start')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Failed Calls');

        $headers = ['Date/Time', 'Caller', 'Client', 'Callee', 'Ring Time (s)', 'Disposition', 'Reason'];
        $this->writeHeaders($sheet, $headers);

        $row = 2;
        foreach ($records as $r) {
            $sheet->setCellValue("A{$row}", $r->call_start?->format('Y-m-d H:i:s'));
            $sheet->setCellValue("B{$row}", $r->caller);
            $sheet->setCellValue("C{$row}", $r->user?->name ?? '');
            $sheet->setCellValue("D{$row}", $r->callee);
            $sheet->setCellValue("E{$row}", $r->duration);
            $sheet->setCellValue("F{$row}", $r->disposition);
            $sheet->setCellValue("G{$row}", $r->hangup_cause ? str_replace('_', ' ', $r->hangup_cause) : '');

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0FDF4');
            }
            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'failed-calls-' . $dateFrom->format('Y-m-d') . '-to-' . $dateTo->format('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Export call summary as XLSX.
     */
    public function exportCallSummary(Request $request)
    {
        $descendantIds = auth()->user()->descendantIds();
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, 30);

        $query = DB::table('cdr_summary_daily')
            ->whereBetween('date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->whereIn('user_id', $descendantIds);

        if ($request->filled('user_id') && in_array((int) $request->user_id, $descendantIds)) {
            $query->where('user_id', $request->user_id);
        }

        $rows = $query->selectRaw('
                date,
                SUM(total_calls) as total_calls,
                SUM(answered_calls) as answered_calls,
                SUM(total_calls) - SUM(answered_calls) as failed_calls,
                SUM(total_billable) as total_billable,
                SUM(total_cost) as total_cost,
                SUM(total_reseller_cost) as reseller_cost
            ')
            ->groupBy('date')
            ->orderByDesc('date')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Call Summary');

        $headers = ['Date', 'Total Calls', 'Answered', 'Failed', 'ASR %', 'Billed Min', 'Client Cost', 'My Cost', 'Profit'];
        $this->writeHeaders($sheet, $headers);

        $row = 2;
        foreach ($rows as $r) {
            $total = $r->total_calls ?? 0;
            $answered = $r->answered_calls ?? 0;
            $failed = $total - $answered;
            $asr = $total > 0 ? round(($answered / $total) * 100, 1) : 0;
            $clientCost = $r->total_cost ?? 0;
            $myCost = $r->reseller_cost ?? 0;
            $profit = $clientCost - $myCost;

            $sheet->setCellValue("A{$row}", Carbon::parse($r->date)->format('Y-m-d'));
            $sheet->setCellValue("B{$row}", $total);
            $sheet->setCellValue("C{$row}", $answered);
            $sheet->setCellValue("D{$row}", $failed);
            $sheet->setCellValue("E{$row}", $asr . '%');
            $sheet->setCellValue("F{$row}", round(($r->total_billable ?? 0) / 60));
            $sheet->setCellValue("G{$row}", $clientCost);
            $sheet->setCellValue("H{$row}", $myCost);
            $sheet->setCellValue("I{$row}", $profit);

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:I{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0FDF4');
            }
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('G2:I' . max($row - 1, 2))->getNumberFormat()->setFormatCode('#,##0.0000');

        $filename = 'call-summary-' . $dateFrom->format('Y-m-d') . '-to-' . $dateTo->format('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function writeHeaders($sheet, array $headers): void
    {
        foreach ($headers as $col => $header) {
            $cell = chr(65 + $col) . '1';
            $sheet->setCellValue($cell, $header);
        }

        $lastCol = chr(64 + count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);
    }

    private function getClientList(array $descendantIds): array
    {
        return User::whereIn('id', $descendantIds)
            ->where('role', 'client')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(function ($u) {
                return ['id' => $u->id, 'name' => $u->name, 'email' => $u->email];
            })
            ->values()
            ->toArray();
    }

    private function resolveDateRange(Request $request, int $maxDays = 7): array
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::today()->startOfDay();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::today()->endOfDay();

        if ($dateFrom->gt($dateTo)) $dateFrom = $dateTo->copy()->startOfDay();
        if ($dateFrom->diffInDays($dateTo) > $maxDays) $dateTo = $dateFrom->copy()->addDays($maxDays)->endOfDay();

        return [$dateFrom, $dateTo];
    }

    private function getFilteredStats(Carbon $dateFrom, Carbon $dateTo, array $ids, ?string $disposition = null, bool $failedOnly = false, ?int $userId = null): array
    {
        $query = CallRecord::query()
            ->whereBetween('call_start', [$dateFrom, $dateTo])
            ->whereIn('user_id', $ids);

        if ($userId && in_array($userId, $ids)) $query->where('user_id', $userId);
        if ($disposition) $query->where('disposition', $disposition);
        if ($failedOnly) $query->whereIn('disposition', ['NO ANSWER', 'BUSY', 'FAILED', 'CANCEL']);

        $row = $query->selectRaw('
            COUNT(*) as total,
            COALESCE(SUM(duration), 0) as duration,
            COALESCE(SUM(billsec), 0) as billsec,
            COALESCE(SUM(total_cost), 0) as cost
        ')->first();

        return [
            'total' => $row->total ?? 0,
            'duration' => $row->duration ?? 0,
            'billsec' => $row->billsec ?? 0,
            'cost' => $row->cost ?? 0,
        ];
    }
}

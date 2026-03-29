<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\CallRecord;
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
    public function activeCalls(Request $request)
    {
        $query = CallRecord::query()
            ->where('status', 'in_progress')
            ->where('user_id', auth()->id());

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller', 'like', "{$search}%")->orWhere('callee', 'like', "{$search}%");
            });
        }

        $calls = $query->with('sipAccount:id,username')
            ->orderByDesc('call_start')
            ->limit(100)
            ->get();

        return view('client.reports.active-calls', compact('calls'));
    }

    public function successCalls(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $query = CallRecord::query()
            ->whereBetween('call_start', [$dateFrom, $dateTo])
            ->where('user_id', auth()->id())
            ->where('disposition', 'ANSWERED');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller', 'like', "{$search}%")->orWhere('callee', 'like', "{$search}%");
            });
        }

        $records = $query->orderByDesc('call_start')->paginate(25);

        $stats = $this->getFilteredStats($dateFrom, $dateTo, 'ANSWERED');

        return view('client.reports.success-calls', compact('records', 'stats', 'dateFrom', 'dateTo'));
    }

    public function failedCalls(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $query = CallRecord::query()
            ->whereBetween('call_start', [$dateFrom, $dateTo])
            ->where('user_id', auth()->id())
            ->whereIn('disposition', ['NO ANSWER', 'BUSY', 'FAILED', 'CANCEL']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller', 'like', "{$search}%")->orWhere('callee', 'like', "{$search}%");
            });
        }

        $records = $query->orderByDesc('call_start')->paginate(50);

        $stats = $this->getFilteredStats($dateFrom, $dateTo, null, true);

        return view('client.reports.failed-calls', compact('records', 'stats', 'dateFrom', 'dateTo'));
    }

    public function callSummary(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, 30);

        $summary = DB::table('cdr_summary_daily')
            ->whereBetween('date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->where('user_id', auth()->id())
            ->selectRaw('date, SUM(total_calls) as total_calls, SUM(answered_calls) as answered_calls,
                SUM(total_calls) - SUM(answered_calls) as failed_calls, SUM(total_duration) as total_duration,
                SUM(total_billable) as total_billable, SUM(total_cost) as total_cost')
            ->groupBy('date')
            ->orderByDesc('date')
            ->paginate(31);

        $totals = DB::table('cdr_summary_daily')
            ->whereBetween('date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->where('user_id', auth()->id())
            ->selectRaw('SUM(total_calls) as total_calls, SUM(answered_calls) as answered_calls,
                SUM(total_duration) as total_duration, SUM(total_billable) as total_billable,
                SUM(total_cost) as total_cost')
            ->first();

        return view('client.reports.call-summary', compact('summary', 'totals', 'dateFrom', 'dateTo'));
    }

    public function exportSuccessCalls(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $records = CallRecord::query()
            ->whereBetween('call_start', [$dateFrom, $dateTo])
            ->where('user_id', auth()->id())
            ->where('disposition', 'ANSWERED')
            ->orderByDesc('call_start')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Success Calls');
        $this->writeHeaders($sheet, ['Date/Time', 'Caller', 'Callee', 'Duration', 'Rate/Min', 'Cost']);

        $row = 2;
        foreach ($records as $r) {
            $sheet->setCellValue("A{$row}", $r->call_start?->format('Y-m-d H:i:s'));
            $sheet->setCellValue("B{$row}", $r->caller);
            $sheet->setCellValue("C{$row}", $r->callee);
            $sheet->setCellValue("D{$row}", sprintf('%d:%02d', intdiv($r->billable_duration, 60), $r->billable_duration % 60));
            $sheet->setCellValue("E{$row}", (float) $r->rate_per_minute);
            $sheet->setCellValue("F{$row}", (float) $r->total_cost);
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2FF');
            }
            $row++;
        }

        foreach (range('A', 'F') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        $sheet->getStyle('E2:F' . max($row - 1, 2))->getNumberFormat()->setFormatCode('#,##0.0000');

        $filename = 'success-calls-' . $dateFrom->format('Y-m-d') . '-to-' . $dateTo->format('Y-m-d') . '.xlsx';
        return response()->streamDownload(fn () => (new Xlsx($spreadsheet))->save('php://output'), $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportFailedCalls(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $records = CallRecord::query()
            ->whereBetween('call_start', [$dateFrom, $dateTo])
            ->where('user_id', auth()->id())
            ->whereIn('disposition', ['NO ANSWER', 'BUSY', 'FAILED', 'CANCEL'])
            ->orderByDesc('call_start')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Failed Calls');
        $this->writeHeaders($sheet, ['Date/Time', 'Caller', 'Callee', 'Ring Time (s)', 'Disposition']);

        $row = 2;
        foreach ($records as $r) {
            $sheet->setCellValue("A{$row}", $r->call_start?->format('Y-m-d H:i:s'));
            $sheet->setCellValue("B{$row}", $r->caller);
            $sheet->setCellValue("C{$row}", $r->callee);
            $sheet->setCellValue("D{$row}", $r->duration);
            $sheet->setCellValue("E{$row}", $r->disposition);
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:E{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2FF');
            }
            $row++;
        }

        foreach (range('A', 'E') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

        $filename = 'failed-calls-' . $dateFrom->format('Y-m-d') . '-to-' . $dateTo->format('Y-m-d') . '.xlsx';
        return response()->streamDownload(fn () => (new Xlsx($spreadsheet))->save('php://output'), $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportCallSummary(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, 30);

        $rows = DB::table('cdr_summary_daily')
            ->whereBetween('date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->where('user_id', auth()->id())
            ->selectRaw('date, SUM(total_calls) as total_calls, SUM(answered_calls) as answered_calls,
                SUM(total_calls) - SUM(answered_calls) as failed_calls, SUM(total_billable) as total_billable,
                SUM(total_cost) as total_cost')
            ->groupBy('date')
            ->orderByDesc('date')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Call Summary');
        $this->writeHeaders($sheet, ['Date', 'Total Calls', 'Answered', 'Failed', 'ASR %', 'Billed Min', 'Cost']);

        $row = 2;
        foreach ($rows as $r) {
            $asr = ($r->total_calls ?? 0) > 0 ? round((($r->answered_calls ?? 0) / $r->total_calls) * 100, 1) : 0;
            $sheet->setCellValue("A{$row}", Carbon::parse($r->date)->format('Y-m-d'));
            $sheet->setCellValue("B{$row}", $r->total_calls ?? 0);
            $sheet->setCellValue("C{$row}", $r->answered_calls ?? 0);
            $sheet->setCellValue("D{$row}", ($r->total_calls ?? 0) - ($r->answered_calls ?? 0));
            $sheet->setCellValue("E{$row}", $asr . '%');
            $sheet->setCellValue("F{$row}", round(($r->total_billable ?? 0) / 60));
            $sheet->setCellValue("G{$row}", $r->total_cost ?? 0);
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2FF');
            }
            $row++;
        }

        foreach (range('A', 'G') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        $sheet->getStyle('G2:G' . max($row - 1, 2))->getNumberFormat()->setFormatCode('#,##0.0000');

        $filename = 'call-summary-' . $dateFrom->format('Y-m-d') . '-to-' . $dateTo->format('Y-m-d') . '.xlsx';
        return response()->streamDownload(fn () => (new Xlsx($spreadsheet))->save('php://output'), $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function writeHeaders($sheet, array $headers): void
    {
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(chr(65 + $col) . '1', $header);
        }
        $lastCol = chr(64 + count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);
    }

    private function resolveDateRange(Request $request, int $maxDays = 7): array
    {
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : Carbon::today()->startOfDay();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : Carbon::today()->endOfDay();
        if ($dateFrom->gt($dateTo)) $dateFrom = $dateTo->copy()->startOfDay();
        if ($dateFrom->diffInDays($dateTo) > $maxDays) $dateTo = $dateFrom->copy()->addDays($maxDays)->endOfDay();
        return [$dateFrom, $dateTo];
    }

    private function getFilteredStats(Carbon $dateFrom, Carbon $dateTo, ?string $disposition = null, bool $failedOnly = false): array
    {
        $query = CallRecord::query()
            ->whereBetween('call_start', [$dateFrom, $dateTo])
            ->where('user_id', auth()->id());

        if ($disposition) $query->where('disposition', $disposition);
        if ($failedOnly) $query->whereIn('disposition', ['NO ANSWER', 'BUSY', 'FAILED', 'CANCEL']);

        $row = $query->selectRaw('COUNT(*) as total, COALESCE(SUM(duration), 0) as duration, COALESCE(SUM(billsec), 0) as billsec, COALESCE(SUM(total_cost), 0) as cost')->first();

        return ['total' => $row->total ?? 0, 'duration' => $row->duration ?? 0, 'billsec' => $row->billsec ?? 0, 'cost' => $row->cost ?? 0];
    }
}

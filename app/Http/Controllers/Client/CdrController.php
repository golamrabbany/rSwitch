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

class CdrController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $query = CallRecord::query()
            ->whereBetween('call_start', [$dateFrom, $dateTo])
            ->where('user_id', $userId);

        $this->applyFilters($query, $request);

        $records = $query->with('sipAccount:id,username')
            ->orderByDesc('call_start')
            ->paginate(50);

        $stats = $this->getStats($request, $dateFrom, $dateTo, $userId);

        return view('client.cdr.index', compact('records', 'stats', 'dateFrom', 'dateTo'));
    }

    public function show(Request $request, string $uuid)
    {
        $userId = auth()->id();

        if ($request->filled('date')) {
            $date = Carbon::parse($request->date);
            $record = CallRecord::where('uuid', $uuid)
                ->where('user_id', $userId)
                ->whereBetween('call_start', [
                    $date->copy()->startOfDay(),
                    $date->copy()->endOfDay(),
                ])
                ->firstOrFail();
        } else {
            $record = CallRecord::where('uuid', $uuid)
                ->where('user_id', $userId)
                ->whereBetween('call_start', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ])
                ->firstOrFail();
        }

        $record->load('sipAccount:id,username,status');

        $hasRecording = file_exists(config('filesystems.disks.recordings.root') . '/' . $record->uuid . '.wav');

        return view('client.cdr.show', compact('record', 'hasRecording'));
    }

    public function export(Request $request)
    {
        $userId = auth()->id();
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, maxDays: 7);

        $query = CallRecord::query()
            ->whereBetween('call_start', [$dateFrom, $dateTo])
            ->where('user_id', $userId);

        $this->applyFilters($query, $request);

        $records = $query->with('sipAccount:id,username')->orderByDesc('call_start')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Call Records');

        $headers = ['Date/Time', 'Caller', 'Callee', 'Destination', 'Duration', 'Billable', 'Rate/Min', 'Cost', 'Disposition', 'SIP Account'];
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

        $row = 2;
        foreach ($records as $r) {
            $sheet->setCellValue("A{$row}", $r->call_start?->format('Y-m-d H:i:s'));
            $sheet->setCellValue("B{$row}", $r->caller);
            $sheet->setCellValue("C{$row}", $r->callee);
            $sheet->setCellValue("D{$row}", $r->destination ?: '');
            $sheet->setCellValue("E{$row}", sprintf('%d:%02d', intdiv($r->duration, 60), $r->duration % 60));
            $sheet->setCellValue("F{$row}", sprintf('%d:%02d', intdiv($r->billable_duration, 60), $r->billable_duration % 60));
            $sheet->setCellValue("G{$row}", (float) $r->rate_per_minute);
            $sheet->setCellValue("H{$row}", (float) $r->total_cost);
            $sheet->setCellValue("I{$row}", $r->disposition);
            $sheet->setCellValue("J{$row}", $r->sipAccount?->username ?? '');

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:J{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2FF');
            }
            $row++;
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('G2:H' . max($row - 1, 2))->getNumberFormat()->setFormatCode('#,##0.0000');

        $filename = 'call-records-' . $dateFrom->format('Y-m-d') . '-to-' . $dateTo->format('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function resolveDateRange(Request $request, int $maxDays = 31): array
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

        if ($dateFrom->diffInDays($dateTo) > $maxDays) {
            $dateTo = $dateFrom->copy()->addDays($maxDays)->endOfDay();
        }

        return [$dateFrom, $dateTo];
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('disposition')) {
            $query->where('disposition', $request->disposition);
        }

        if ($request->filled('call_type')) {
            $query->where('call_type', $request->call_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller', 'like', "{$search}%")
                  ->orWhere('callee', 'like', "{$search}%");
            });
        }
    }

    private function getStats(Request $request, Carbon $dateFrom, Carbon $dateTo, int $userId): array
    {
        $canUseSummary = !$request->filled('search') && !$request->filled('disposition');

        if ($canUseSummary) {
            $row = DB::table('cdr_summary_daily')
                ->whereBetween('date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->where('user_id', $userId)
                ->selectRaw('
                    COALESCE(SUM(total_calls), 0) as total_calls,
                    COALESCE(SUM(answered_calls), 0) as answered_calls,
                    COALESCE(SUM(total_duration), 0) as total_duration,
                    COALESCE(SUM(total_billable), 0) as total_billable,
                    COALESCE(SUM(total_cost), 0) as total_cost
                ')->first();

            return (array) $row;
        }

        $statsQuery = CallRecord::query()
            ->whereBetween('call_start', [$dateFrom, $dateTo])
            ->where('user_id', $userId);

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
}

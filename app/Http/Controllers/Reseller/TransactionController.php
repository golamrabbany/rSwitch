<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $descendantIds = auth()->user()->descendantIds();

        $query = Transaction::with('user')
            ->whereIn('user_id', $descendantIds);

        if ($request->filled('user_id') && in_array($request->user_id, $descendantIds)) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(30);

        $clients = User::whereIn('id', $descendantIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(function ($u) {
                return ['id' => $u->id, 'name' => $u->name, 'email' => $u->email];
            })
            ->values()
            ->toArray();

        $currentBalance = auth()->user()->balance;

        // Stats
        $statsQuery = Transaction::whereIn('user_id', $descendantIds);
        if ($request->filled('user_id') && in_array($request->user_id, $descendantIds)) {
            $statsQuery->where('user_id', $request->user_id);
        }
        if ($request->filled('type')) {
            $statsQuery->where('type', $request->type);
        }
        if ($request->filled('date_from')) {
            $statsQuery->where('created_at', '>=', $request->date_from . ' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $statsQuery->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $stats = $statsQuery->selectRaw('
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END), 0) as total_credit,
            COALESCE(SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END), 0) as total_debit
        ')->first();

        return view('reseller.transactions.index', compact('transactions', 'clients', 'currentBalance', 'stats'));
    }

    public function export(Request $request)
    {
        $descendantIds = auth()->user()->descendantIds();

        $query = Transaction::with('user')
            ->whereIn('user_id', $descendantIds);

        if ($request->filled('user_id') && in_array($request->user_id, $descendantIds)) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $records = $query->orderByDesc('created_at')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Client Transactions');

        $headers = ['Date/Time', 'User', 'Type', 'Description', 'Amount', 'Balance After'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(chr(65 + $col) . '1', $header);
        }
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $row = 2;
        foreach ($records as $txn) {
            $sheet->setCellValue("A{$row}", $txn->created_at->format('Y-m-d H:i:s'));
            $sheet->setCellValue("B{$row}", $txn->user_id === auth()->id() ? 'You' : ($txn->user?->name ?? ''));
            $sheet->setCellValue("C{$row}", ucfirst(str_replace('_', ' ', $txn->type)));
            $sheet->setCellValue("D{$row}", $txn->description);
            $sheet->setCellValue("E{$row}", (float) $txn->amount);
            $sheet->setCellValue("F{$row}", (float) $txn->balance_after);

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0FDF4');
            }
            $row++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('E2:F' . max($row - 1, 2))->getNumberFormat()->setFormatCode('#,##0.0000');

        $filename = 'client-transactions-' . now()->format('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}

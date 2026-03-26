<?php

namespace App\Jobs;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExportCdrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes max
    public int $tries = 2;

    public function __construct(
        public int $requestedBy,
        public string $month,        // Format: 2026-03
        public ?int $userId = null,   // Filter by specific user
        public ?int $resellerId = null, // Filter by reseller
    ) {}

    public function handle(): void
    {
        $startDate = Carbon::parse($this->month . '-01')->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();
        $filename = 'cdr-export-' . $this->month;

        if ($this->userId) {
            $filename .= '-user' . $this->userId;
        } elseif ($this->resellerId) {
            $filename .= '-reseller' . $this->resellerId;
        }
        $filename .= '.csv';

        $exportPath = 'exports/' . $filename;
        $fullPath = storage_path('app/' . $exportPath);

        // Ensure directory exists
        Storage::makeDirectory('exports');

        $handle = fopen($fullPath, 'w');

        // CSV header
        fputcsv($handle, [
            'UUID', 'Call Start', 'Call End', 'Caller', 'Callee', 'Destination',
            'Duration', 'Billsec', 'Billable Duration', 'Disposition',
            'Rate/Min', 'Total Cost', 'Reseller Cost',
            'User', 'Reseller', 'Trunk', 'Call Flow', 'Status',
        ]);

        // Process day by day to avoid memory issues
        $totalRows = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            $query = DB::table('call_records')
                ->whereBetween('call_start', [$dayStart, $dayEnd])
                ->leftJoin('users as u', 'u.id', '=', 'call_records.user_id')
                ->leftJoin('users as r', 'r.id', '=', 'call_records.reseller_id')
                ->leftJoin('trunks as t', 't.id', '=', 'call_records.outgoing_trunk_id');

            if ($this->userId) {
                $query->where('call_records.user_id', $this->userId);
            }
            if ($this->resellerId) {
                $query->where('call_records.reseller_id', $this->resellerId);
            }

            $query->select([
                'call_records.uuid', 'call_records.call_start', 'call_records.call_end',
                'call_records.caller', 'call_records.callee', 'call_records.destination',
                'call_records.duration', 'call_records.billsec', 'call_records.billable_duration',
                'call_records.disposition', 'call_records.rate_per_minute',
                'call_records.total_cost', 'call_records.reseller_cost',
                'u.name as user_name', 'r.name as reseller_name',
                't.name as trunk_name', 'call_records.call_flow', 'call_records.status',
            ])
            ->orderBy('call_records.call_start');

            // Stream in chunks to avoid memory issues
            $query->chunk(5000, function ($rows) use ($handle, &$totalRows) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->uuid,
                        $row->call_start,
                        $row->call_end,
                        $row->caller,
                        $row->callee,
                        $row->destination,
                        $row->duration,
                        $row->billsec,
                        $row->billable_duration,
                        $row->disposition,
                        $row->rate_per_minute,
                        $row->total_cost,
                        $row->reseller_cost,
                        $row->user_name,
                        $row->reseller_name,
                        $row->trunk_name,
                        $row->call_flow,
                        $row->status,
                    ]);
                    $totalRows++;
                }
            });

            $currentDate->addDay();
        }

        fclose($handle);

        // Compress the CSV
        $gzPath = $fullPath . '.gz';
        $fp = fopen($fullPath, 'rb');
        $gz = gzopen($gzPath, 'wb9');
        while (!feof($fp)) {
            gzwrite($gz, fread($fp, 8192));
        }
        gzclose($gz);
        fclose($fp);
        unlink($fullPath); // Remove uncompressed

        $fileSize = round(filesize($gzPath) / 1024 / 1024, 2);

        Log::info("CDR export completed", [
            'month' => $this->month,
            'rows' => $totalRows,
            'file' => $gzPath,
            'size_mb' => $fileSize,
            'requested_by' => $this->requestedBy,
        ]);

        // TODO: Send notification to requesting user (email or in-app notification)
    }
}

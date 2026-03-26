<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AggregateCdr extends Command
{
    protected $signature = 'cdr:aggregate {--date= : Specific date (Y-m-d), defaults to today}';

    protected $description = 'Aggregate call records into summary tables (hourly, daily, destination)';

    public function handle(): int
    {
        $date = $this->option('date') ?: now()->toDateString();
        $dateStart = $date . ' 00:00:00';
        $dateEnd = $date . ' 23:59:59';

        $this->info("Aggregating CDR summaries for {$date}...");

        $this->aggregateDaily($date, $dateStart, $dateEnd);
        $this->aggregateHourly($date, $dateStart, $dateEnd);
        $this->aggregateDestination($date, $dateStart, $dateEnd);

        $this->info('CDR aggregation complete.');

        return self::SUCCESS;
    }

    private function aggregateDaily(string $date, string $start, string $end): void
    {
        $rows = DB::select("
            SELECT
                user_id,
                reseller_id,
                ? AS `date`,
                COUNT(*) AS total_calls,
                SUM(CASE WHEN disposition = 'ANSWERED' THEN 1 ELSE 0 END) AS answered_calls,
                COALESCE(SUM(duration), 0) AS total_duration,
                COALESCE(SUM(billable_duration), 0) AS total_billable,
                COALESCE(SUM(total_cost), 0) AS total_cost,
                COALESCE(SUM(reseller_cost), 0) AS total_reseller_cost,
                COALESCE(SUM(trunk_cost), 0) AS total_trunk_cost
            FROM call_records
            WHERE call_start BETWEEN ? AND ?
              AND user_id IS NOT NULL
            GROUP BY user_id, reseller_id
        ", [$date, $start, $end]);

        $count = 0;

        foreach ($rows as $row) {
            $totalCalls = (int) $row->total_calls;
            $answeredCalls = (int) $row->answered_calls;
            $asr = $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 2) : null;
            $acd = $answeredCalls > 0 ? round($row->total_duration / $answeredCalls, 2) : null;

            DB::statement("
                INSERT INTO cdr_summary_daily
                    (user_id, reseller_id, `date`, total_calls, answered_calls,
                     total_duration, total_billable, total_cost, total_reseller_cost, total_trunk_cost,
                     asr, acd, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    reseller_id = VALUES(reseller_id),
                    total_calls = VALUES(total_calls),
                    answered_calls = VALUES(answered_calls),
                    total_duration = VALUES(total_duration),
                    total_billable = VALUES(total_billable),
                    total_cost = VALUES(total_cost),
                    total_reseller_cost = VALUES(total_reseller_cost),
                    total_trunk_cost = VALUES(total_trunk_cost),
                    asr = VALUES(asr),
                    acd = VALUES(acd),
                    updated_at = NOW()
            ", [
                $row->user_id, $row->reseller_id, $date,
                $totalCalls, $answeredCalls,
                $row->total_duration, $row->total_billable,
                $row->total_cost, $row->total_reseller_cost, $row->total_trunk_cost,
                $asr, $acd,
            ]);

            $count++;
        }

        $this->line("  Daily: {$count} user-day rows upserted.");
    }

    private function aggregateHourly(string $date, string $start, string $end): void
    {
        $rows = DB::select("
            SELECT
                user_id,
                reseller_id,
                DATE_FORMAT(call_start, '%Y-%m-%d %H:00:00') AS hour_start,
                COUNT(*) AS total_calls,
                SUM(CASE WHEN disposition = 'ANSWERED' THEN 1 ELSE 0 END) AS answered_calls,
                SUM(CASE WHEN disposition != 'ANSWERED' THEN 1 ELSE 0 END) AS failed_calls,
                COALESCE(SUM(duration), 0) AS total_duration,
                COALESCE(SUM(billable_duration), 0) AS total_billable,
                COALESCE(SUM(total_cost), 0) AS total_cost,
                COALESCE(SUM(reseller_cost), 0) AS total_reseller_cost,
                COALESCE(SUM(trunk_cost), 0) AS total_trunk_cost
            FROM call_records
            WHERE call_start BETWEEN ? AND ?
              AND user_id IS NOT NULL
            GROUP BY user_id, reseller_id, DATE_FORMAT(call_start, '%Y-%m-%d %H:00:00')
        ", [$start, $end]);

        $count = 0;

        foreach ($rows as $row) {
            $totalCalls = (int) $row->total_calls;
            $answeredCalls = (int) $row->answered_calls;
            $asr = $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 2) : null;
            $acd = $answeredCalls > 0 ? round($row->total_duration / $answeredCalls, 2) : null;

            DB::statement("
                INSERT INTO cdr_summary_hourly
                    (user_id, reseller_id, hour_start, total_calls, answered_calls, failed_calls,
                     total_duration, total_billable, total_cost, total_reseller_cost, total_trunk_cost,
                     asr, acd, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    reseller_id = VALUES(reseller_id),
                    total_calls = VALUES(total_calls),
                    answered_calls = VALUES(answered_calls),
                    failed_calls = VALUES(failed_calls),
                    total_duration = VALUES(total_duration),
                    total_billable = VALUES(total_billable),
                    total_cost = VALUES(total_cost),
                    total_reseller_cost = VALUES(total_reseller_cost),
                    total_trunk_cost = VALUES(total_trunk_cost),
                    asr = VALUES(asr),
                    acd = VALUES(acd),
                    updated_at = NOW()
            ", [
                $row->user_id, $row->reseller_id, $row->hour_start,
                $totalCalls, $answeredCalls, $row->failed_calls,
                $row->total_duration, $row->total_billable,
                $row->total_cost, $row->total_reseller_cost, $row->total_trunk_cost,
                $asr, $acd,
            ]);

            $count++;
        }

        $this->line("  Hourly: {$count} user-hour rows upserted.");
    }

    private function aggregateDestination(string $date, string $start, string $end): void
    {
        $rows = DB::select("
            SELECT
                ? AS `date`,
                COALESCE(matched_prefix, 'unknown') AS matched_prefix,
                COALESCE(destination, callee) AS destination,
                outgoing_trunk_id,
                COUNT(*) AS total_calls,
                SUM(CASE WHEN disposition = 'ANSWERED' THEN 1 ELSE 0 END) AS answered_calls,
                COALESCE(SUM(duration), 0) AS total_duration,
                COALESCE(SUM(total_cost), 0) AS total_cost
            FROM call_records
            WHERE call_start BETWEEN ? AND ?
              AND call_flow = 'outbound'
              AND matched_prefix IS NOT NULL
            GROUP BY matched_prefix, COALESCE(destination, callee), outgoing_trunk_id
        ", [$date, $start, $end]);

        $count = 0;

        foreach ($rows as $row) {
            $totalCalls = (int) $row->total_calls;
            $answeredCalls = (int) $row->answered_calls;
            $asr = $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 2) : null;
            $acd = $answeredCalls > 0 ? round($row->total_duration / $answeredCalls, 2) : null;

            // Truncate destination to 100 chars for the column
            $dest = substr($row->destination ?: '', 0, 100);

            DB::statement("
                INSERT INTO cdr_summary_destination
                    (`date`, matched_prefix, destination, outgoing_trunk_id,
                     total_calls, answered_calls, total_duration, total_cost,
                     asr, acd, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    destination = VALUES(destination),
                    total_calls = VALUES(total_calls),
                    answered_calls = VALUES(answered_calls),
                    total_duration = VALUES(total_duration),
                    total_cost = VALUES(total_cost),
                    asr = VALUES(asr),
                    acd = VALUES(acd),
                    updated_at = NOW()
            ", [
                $date, $row->matched_prefix, $dest, $row->outgoing_trunk_id,
                $totalCalls, $answeredCalls, $row->total_duration, $row->total_cost,
                $asr, $acd,
            ]);

            $count++;
        }

        $this->line("  Destination: {$count} prefix-trunk rows upserted.");
    }
}

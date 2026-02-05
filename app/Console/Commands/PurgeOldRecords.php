<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeOldRecords extends Command
{
    protected $signature = 'data:purge
        {--dry-run : Preview what would be deleted without actually deleting}
        {--cdr-days= : Override CDR retention days (default from system settings)}
        {--audit-days= : Override audit log retention days (default from system settings)}';

    protected $description = 'Purge old call records and audit logs based on retention settings';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $cdrDays = (int) ($this->option('cdr-days') ?: SystemSetting::get('cdr_retention_days', 365));
        $auditDays = (int) ($this->option('audit-days') ?: SystemSetting::get('audit_retention_days', 180));

        $cdrCutoff = now()->subDays($cdrDays);
        $auditCutoff = now()->subDays($auditDays);

        $this->info("Data retention purge" . ($dryRun ? ' [DRY RUN]' : ''));
        $this->newLine();

        // --- Call Records ---
        $this->info("CDR retention: {$cdrDays} days (cutoff: {$cdrCutoff->toDateString()})");

        $cdrCount = DB::table('call_records')
            ->where('call_start', '<', $cdrCutoff)
            ->count();

        $this->line("  Call records to purge: {$cdrCount}");

        if ($cdrCount > 0 && !$dryRun) {
            // Delete in batches to avoid lock contention on partitioned table
            $totalDeleted = 0;
            do {
                $deleted = DB::table('call_records')
                    ->where('call_start', '<', $cdrCutoff)
                    ->limit(5000)
                    ->delete();
                $totalDeleted += $deleted;
                if ($deleted > 0) {
                    $this->line("  Deleted batch: {$deleted} (total: {$totalDeleted})");
                }
            } while ($deleted > 0);
            $this->info("  CDR purge complete: {$totalDeleted} records deleted.");
        }

        // --- CDR Summary tables ---
        $summaryTables = [
            'cdr_summary_daily' => 'date',
            'cdr_summary_hourly' => 'hour_start',
            'cdr_summary_destination' => 'date',
        ];

        foreach ($summaryTables as $table => $dateColumn) {
            $count = DB::table($table)->where($dateColumn, '<', $cdrCutoff->toDateString())->count();
            $this->line("  {$table} to purge: {$count}");

            if ($count > 0 && !$dryRun) {
                $deleted = DB::table($table)->where($dateColumn, '<', $cdrCutoff->toDateString())->delete();
                $this->info("  {$table} purge complete: {$deleted} rows deleted.");
            }
        }

        $this->newLine();

        // --- Audit Logs ---
        $this->info("Audit log retention: {$auditDays} days (cutoff: {$auditCutoff->toDateString()})");

        $auditCount = AuditLog::where('created_at', '<', $auditCutoff)->count();
        $this->line("  Audit logs to purge: {$auditCount}");

        if ($auditCount > 0 && !$dryRun) {
            $totalDeleted = 0;
            do {
                $deleted = AuditLog::where('created_at', '<', $auditCutoff)
                    ->limit(5000)
                    ->delete();
                $totalDeleted += $deleted;
            } while ($deleted > 0);
            $this->info("  Audit log purge complete: {$totalDeleted} records deleted.");
        }

        $this->newLine();

        // --- Summary ---
        $this->table(['Category', 'Retention', 'Records'], [
            ['Call Records', "{$cdrDays} days", $cdrCount],
            ['CDR Summaries', "{$cdrDays} days", collect($summaryTables)->map(fn ($dateColumn, $table) => DB::table($table)->where($dateColumn, '<', $cdrCutoff->toDateString())->count())->sum()],
            ['Audit Logs', "{$auditDays} days", $auditCount],
        ]);

        if ($dryRun) {
            $this->warn('Dry run — no records were deleted.');
        } else {
            $this->info('Purge complete.');
        }

        return Command::SUCCESS;
    }
}

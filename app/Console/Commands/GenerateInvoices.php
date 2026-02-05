<?php

namespace App\Console\Commands;

use App\Models\Did;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateInvoices extends Command
{
    protected $signature = 'billing:generate-invoices
        {--month= : Month to invoice (Y-m), defaults to previous month}
        {--dry-run : Preview without creating invoices}';

    protected $description = 'Generate monthly invoices for all users with call or DID charges';

    public function handle(): int
    {
        $monthInput = $this->option('month') ?: now()->subMonth()->format('Y-m');
        $dryRun = $this->option('dry-run');

        $periodStart = $monthInput . '-01';
        $periodEnd = date('Y-m-t', strtotime($periodStart));

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Generating invoices for {$periodStart} to {$periodEnd}...");

        // Get call charges per user from cdr_summary_daily
        $callCharges = DB::select("
            SELECT user_id, SUM(total_cost) AS total_call_charges
            FROM cdr_summary_daily
            WHERE `date` BETWEEN ? AND ?
            GROUP BY user_id
        ", [$periodStart, $periodEnd]);

        $callChargeMap = collect($callCharges)->keyBy('user_id');

        // Get DID charges per user (monthly_price * 1 month for active DIDs)
        $didCharges = Did::where('status', 'active')
            ->whereNotNull('assigned_to_user_id')
            ->select('assigned_to_user_id', DB::raw('SUM(monthly_price) AS total_did_charges'))
            ->groupBy('assigned_to_user_id')
            ->pluck('total_did_charges', 'assigned_to_user_id');

        // Get all billable users
        $userIds = collect($callChargeMap->keys())
            ->merge($didCharges->keys())
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            $this->info('No billable activity found for this period.');
            return self::SUCCESS;
        }

        // Check for existing invoices in this period to avoid duplicates
        $existingInvoices = Invoice::where('period_start', $periodStart)
            ->where('period_end', $periodEnd)
            ->pluck('user_id')
            ->toArray();

        $created = 0;
        $skipped = 0;
        $rows = [];

        foreach ($userIds as $userId) {
            if (in_array($userId, $existingInvoices)) {
                $skipped++;
                continue;
            }

            $callAmount = $callChargeMap->has($userId)
                ? (string) $callChargeMap->get($userId)->total_call_charges
                : '0.0000';

            $didAmount = $didCharges->has($userId)
                ? (string) $didCharges->get($userId)
                : '0.0000';

            $totalAmount = bcadd($callAmount, $didAmount, 4);

            // Skip zero-amount invoices
            if (bccomp($totalAmount, '0', 4) <= 0) {
                continue;
            }

            $user = User::find($userId);
            if (!$user) {
                continue;
            }

            $rows[] = [
                'user' => $user->name,
                'calls' => number_format((float) $callAmount, 4),
                'dids' => number_format((float) $didAmount, 4),
                'total' => number_format((float) $totalAmount, 4),
            ];

            if (!$dryRun) {
                $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . str_pad(
                    Invoice::whereDate('created_at', today())->count() + 1,
                    5, '0', STR_PAD_LEFT
                );

                Invoice::create([
                    'invoice_number' => $invoiceNumber,
                    'user_id' => $userId,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'call_charges' => $callAmount,
                    'did_charges' => $didAmount,
                    'tax_amount' => '0.0000',
                    'total_amount' => $totalAmount,
                    'status' => 'draft',
                    'due_date' => now()->addDays(15)->toDateString(),
                ]);

                $created++;
            }
        }

        if (!empty($rows)) {
            $this->table(['User', 'Call Charges', 'DID Charges', 'Total'], $rows);
        }

        if ($dryRun) {
            $this->info("Would create " . count($rows) . " invoice(s). {$skipped} already exist.");
        } else {
            $this->info("{$created} invoice(s) created. {$skipped} skipped (already exist).");
        }

        return self::SUCCESS;
    }
}

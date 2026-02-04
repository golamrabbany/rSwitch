<?php

namespace App\Console\Commands;

use App\Exceptions\Billing\InsufficientBalanceException;
use App\Exceptions\Billing\RateNotFoundException;
use App\Models\CallRecord;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\RatingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RateCallRecords extends Command
{
    protected $signature = 'billing:rate-calls
        {--chunk=200 : Number of records to process per batch}
        {--date-from= : Start date (Y-m-d) for partition pruning, defaults to today}
        {--date-to= : End date (Y-m-d) for partition pruning, defaults to today}
        {--dry-run : Preview what would be rated without making changes}';

    protected $description = 'Rate unrated call records (status=in_progress, ANSWERED, billsec>0)';

    private int $rated = 0;
    private int $unbillable = 0;
    private int $failed = 0;
    private int $chargeFailures = 0;

    public function handle(RatingService $ratingService, BalanceService $balanceService): int
    {
        $chunkSize = (int) $this->option('chunk');
        $isDryRun = (bool) $this->option('dry-run');

        $dateFrom = $this->option('date-from')
            ? Carbon::parse($this->option('date-from'))->startOfDay()
            : Carbon::today()->startOfDay();

        $dateTo = $this->option('date-to')
            ? Carbon::parse($this->option('date-to'))->endOfDay()
            : Carbon::today()->endOfDay();

        $this->info("Rating call records from {$dateFrom->toDateString()} to {$dateTo->toDateString()}");

        if ($isDryRun) {
            $this->warn('DRY RUN mode — no changes will be made');
        }

        $total = CallRecord::query()
            ->where('status', 'in_progress')
            ->where('disposition', 'ANSWERED')
            ->where('billsec', '>', 0)
            ->whereBetween('call_start', [$dateFrom, $dateTo])
            ->count();

        $this->info("Found {$total} unrated call records to process.");

        if ($total === 0) {
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $userCache = [];

        // Use while-loop instead of chunk() to avoid offset pagination issues
        // when mutating the status column used in the WHERE clause
        do {
            $records = CallRecord::query()
                ->where('status', 'in_progress')
                ->where('disposition', 'ANSWERED')
                ->where('billsec', '>', 0)
                ->whereBetween('call_start', [$dateFrom, $dateTo])
                ->orderBy('call_start')
                ->limit($chunkSize)
                ->get();

            foreach ($records as $callRecord) {
                try {
                    if ($isDryRun) {
                        $this->previewRate($ratingService, $callRecord);
                    } else {
                        $this->rateAndCharge($ratingService, $balanceService, $callRecord, $userCache);
                    }
                } catch (\Throwable $e) {
                    $this->failed++;
                    Log::error('RateCallRecords: unexpected error', [
                        'call_record_id' => $callRecord->id,
                        'error' => $e->getMessage(),
                    ]);

                    if (!$isDryRun) {
                        $callRecord->update([
                            'status' => 'failed',
                            'rated_at' => now(),
                        ]);
                    }
                }

                $bar->advance();
            }
        } while ($records->isNotEmpty() && !$isDryRun);

        // For dry-run, only process one batch (records won't change status)
        if ($isDryRun && $total > $chunkSize) {
            $this->newLine();
            $this->warn("Dry-run preview limited to first {$chunkSize} records.");
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total processed', $this->rated + $this->unbillable + $this->failed],
                ['Rated successfully', $this->rated],
                ['Unbillable', $this->unbillable],
                ['Failed', $this->failed],
                ['Charge failures (balance)', $this->chargeFailures],
            ]
        );

        Log::info('RateCallRecords completed', [
            'rated' => $this->rated,
            'unbillable' => $this->unbillable,
            'failed' => $this->failed,
            'charge_failures' => $this->chargeFailures,
        ]);

        return self::SUCCESS;
    }

    private function rateAndCharge(
        RatingService $ratingService,
        BalanceService $balanceService,
        CallRecord $callRecord,
        array &$userCache,
    ): void {
        try {
            $ratingService->rateCall($callRecord);
        } catch (RateNotFoundException $e) {
            $this->unbillable++;
            Log::info('RateCallRecords: no rate found', [
                'call_record_id' => $callRecord->id,
                'destination' => $e->destination,
                'rate_group_id' => $e->rateGroupId,
            ]);
            $callRecord->update([
                'status' => 'unbillable',
                'rated_at' => now(),
            ]);
            return;
        }

        $callRecord->refresh();

        if ($callRecord->status !== 'rated') {
            $this->unbillable++;
            return;
        }

        $this->rated++;

        if (bccomp((string) $callRecord->total_cost, '0', 4) <= 0) {
            return;
        }

        $userId = $callRecord->user_id;
        if (!isset($userCache[$userId])) {
            $userCache[$userId] = User::find($userId);
        }
        $user = $userCache[$userId];

        if (!$user) {
            return;
        }

        try {
            $balanceService->chargeCall($user, $callRecord);
            $userCache[$userId] = $user;
        } catch (InsufficientBalanceException $e) {
            $this->chargeFailures++;
            Log::warning('RateCallRecords: insufficient balance', [
                'call_record_id' => $callRecord->id,
                'user_id' => $userId,
                'amount' => (string) $callRecord->total_cost,
                'available' => $e->available,
            ]);
        }
    }

    private function previewRate(RatingService $ratingService, CallRecord $callRecord): void
    {
        $destination = $callRecord->destination ?: $callRecord->callee;
        $user = User::find($callRecord->user_id);

        if (!$user || !$user->rate_group_id) {
            $this->unbillable++;
            return;
        }

        try {
            $rates = $ratingService->resolveRates($destination, $user->rate_group_id, $callRecord->call_start);
            $calc = $ratingService->calculateCost($callRecord->billsec, $rates['sell']);

            $this->rated++;
            $this->line(sprintf(
                '  [RATE] CDR %d: %s -> prefix %s, %ds billable, cost %s',
                $callRecord->id,
                $destination,
                $rates['sell']->prefix,
                $calc['billable_duration'],
                $calc['total_cost'],
            ));
        } catch (RateNotFoundException) {
            $this->unbillable++;
        }
    }
}

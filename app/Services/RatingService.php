<?php

namespace App\Services;

use App\Exceptions\Billing\RateNotFoundException;
use App\Models\CallRecord;
use App\Models\Rate;
use App\Models\RateGroup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RatingService
{
    /**
     * Find the best matching rate via longest-prefix-match.
     */
    public function findRate(string $destination, int $rateGroupId, ?Carbon $atTime = null): ?Rate
    {
        $atTime ??= now();

        $cleanNumber = preg_replace('/\D/', '', $destination);

        if (empty($cleanNumber)) {
            return null;
        }

        // Generate all possible prefixes from longest to shortest
        $prefixes = [];
        $len = min(strlen($cleanNumber), 20);
        for ($i = $len; $i >= 1; $i--) {
            $prefixes[] = substr($cleanNumber, 0, $i);
        }

        return Rate::where('rate_group_id', $rateGroupId)
            ->whereIn('prefix', $prefixes)
            ->where('status', 'active')
            ->where('effective_date', '<=', $atTime->toDateString())
            ->where(function ($q) use ($atTime) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>', $atTime->toDateString());
            })
            ->orderByRaw('LENGTH(prefix) DESC')
            ->orderByDesc('effective_date')
            ->first();
    }

    /**
     * Resolve both sell rate (user's group) and admin cost rate (parent group).
     *
     * @return array{sell: Rate, cost: ?Rate}
     * @throws RateNotFoundException
     */
    public function resolveRates(string $destination, int $rateGroupId, ?Carbon $atTime = null): array
    {
        $atTime ??= now();

        $sellRate = $this->findRate($destination, $rateGroupId, $atTime);

        if (!$sellRate) {
            throw new RateNotFoundException($destination, $rateGroupId);
        }

        $costRate = null;
        $rateGroup = RateGroup::find($rateGroupId);

        if ($rateGroup && $rateGroup->parent_rate_group_id) {
            $costRate = $this->findRate($destination, $rateGroup->parent_rate_group_id, $atTime);
        }

        return [
            'sell' => $sellRate,
            'cost' => $costRate,
        ];
    }

    /**
     * Calculate the total cost for a call using bcmath for precision.
     *
     * @return array{billable_duration: int, total_cost: string}
     */
    public function calculateCost(int $billsec, Rate $rate): array
    {
        $minDuration = (int) $rate->min_duration;
        $billingIncrement = max(1, (int) $rate->billing_increment);

        // Apply minimum duration
        $effectiveDuration = max($billsec, $minDuration);

        // Round up to billing increment
        $billableDuration = (int) (ceil($effectiveDuration / $billingIncrement) * $billingIncrement);

        // cost = (billable_duration / 60) * rate_per_minute + connection_fee
        $durationMinutes = bcdiv((string) $billableDuration, '60', 10);
        $usageCost = bcmul($durationMinutes, (string) $rate->rate_per_minute, 10);
        $totalCost = bcadd($usageCost, (string) $rate->connection_fee, 4);

        return [
            'billable_duration' => $billableDuration,
            'total_cost' => $totalCost,
        ];
    }

    /**
     * Rate a single call record: find rates, calculate costs, update CDR.
     *
     * @throws RateNotFoundException
     */
    public function rateCall(CallRecord $callRecord): CallRecord
    {
        $destination = $callRecord->destination ?: $callRecord->callee;

        $user = User::find($callRecord->user_id);

        if (!$user || !$user->rate_group_id) {
            Log::warning('RatingService: user or rate_group_id missing', [
                'call_record_id' => $callRecord->id,
                'user_id' => $callRecord->user_id,
            ]);
            $callRecord->update([
                'status' => 'unbillable',
                'rated_at' => now(),
            ]);
            return $callRecord;
        }

        $rates = $this->resolveRates($destination, $user->rate_group_id, $callRecord->call_start);

        $sellRate = $rates['sell'];
        $costRate = $rates['cost'];

        $sellCalc = $this->calculateCost($callRecord->billsec, $sellRate);

        $costCalc = $costRate
            ? $this->calculateCost($callRecord->billsec, $costRate)
            : ['billable_duration' => $sellCalc['billable_duration'], 'total_cost' => '0.0000'];

        $callRecord->update([
            'matched_prefix' => $sellRate->prefix,
            'rate_per_minute' => $sellRate->rate_per_minute,
            'connection_fee' => $sellRate->connection_fee,
            'rate_group_id' => $user->rate_group_id,
            'billable_duration' => $sellCalc['billable_duration'],
            'total_cost' => $sellCalc['total_cost'],
            'reseller_cost' => $costCalc['total_cost'],
            'status' => 'rated',
            'rated_at' => now(),
        ]);

        return $callRecord;
    }
}

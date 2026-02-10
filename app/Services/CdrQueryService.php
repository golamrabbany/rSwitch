<?php

namespace App\Services;

use App\Models\CallRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service class for CDR (Call Detail Record) queries.
 * Handles partition-aware queries and aggregations.
 */
class CdrQueryService
{
    /**
     * Get a base query scoped to user's visibility with partition optimization.
     */
    public function scopedQuery(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): Builder
    {
        $query = CallRecord::query();

        // Always include date range for partition pruning
        $start = $startDate ?? now()->subDays(30);
        $end = $endDate ?? now();
        $query->whereBetween('call_start', [$start->startOfDay(), $end->endOfDay()]);

        // Scope by user visibility
        if (!$user->isSuperAdmin()) {
            $userIds = $user->descendantIds();
            $query->whereIn('user_id', $userIds);
        }

        return $query;
    }

    /**
     * Get call statistics for dashboard.
     */
    public function getStats(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $start = $startDate ?? now()->subDays(7);
        $end = $endDate ?? now();

        $cacheKey = "cdr_stats_{$user->id}_{$start->format('Ymd')}_{$end->format('Ymd')}";
        $cacheTtl = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($user, $start, $end) {
            // Try cdr_summary_daily first for better performance
            if ($this->canUseSummary($start, $end)) {
                return $this->getStatsFromSummary($user, $start, $end);
            }

            return $this->getStatsFromCallRecords($user, $start, $end);
        });
    }

    /**
     * Check if we can use cdr_summary_daily (complete days only).
     */
    protected function canUseSummary(Carbon $start, Carbon $end): bool
    {
        return $start->isStartOfDay() && $end->isEndOfDay();
    }

    /**
     * Get stats from cdr_summary_daily table.
     */
    protected function getStatsFromSummary(User $user, Carbon $start, Carbon $end): array
    {
        $query = DB::table('cdr_summary_daily')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()]);

        if (!$user->isSuperAdmin()) {
            $userIds = $user->descendantIds();
            $query->whereIn('user_id', $userIds);
        }

        $result = $query->selectRaw('
            SUM(total_calls) as total_calls,
            SUM(answered_calls) as answered_calls,
            SUM(total_duration) as total_duration,
            SUM(total_billsec) as total_billsec,
            SUM(total_cost) as total_cost,
            SUM(total_sell_cost) as total_revenue
        ')->first();

        $totalCalls = (int) ($result->total_calls ?? 0);
        $answeredCalls = (int) ($result->answered_calls ?? 0);

        return [
            'total_calls' => $totalCalls,
            'answered_calls' => $answeredCalls,
            'asr' => $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 1) : 0,
            'total_duration' => (int) ($result->total_duration ?? 0),
            'total_billsec' => (int) ($result->total_billsec ?? 0),
            'total_cost' => (float) ($result->total_cost ?? 0),
            'total_revenue' => (float) ($result->total_revenue ?? 0),
        ];
    }

    /**
     * Get stats directly from call_records table.
     */
    protected function getStatsFromCallRecords(User $user, Carbon $start, Carbon $end): array
    {
        $query = $this->scopedQuery($user, $start, $end);

        $result = $query->selectRaw('
            COUNT(*) as total_calls,
            SUM(CASE WHEN disposition = "ANSWERED" THEN 1 ELSE 0 END) as answered_calls,
            SUM(duration) as total_duration,
            SUM(billsec) as total_billsec,
            SUM(cost) as total_cost,
            SUM(sell_cost) as total_revenue
        ')->first();

        $totalCalls = (int) ($result->total_calls ?? 0);
        $answeredCalls = (int) ($result->answered_calls ?? 0);

        return [
            'total_calls' => $totalCalls,
            'answered_calls' => $answeredCalls,
            'asr' => $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 1) : 0,
            'total_duration' => (int) ($result->total_duration ?? 0),
            'total_billsec' => (int) ($result->total_billsec ?? 0),
            'total_cost' => (float) ($result->total_cost ?? 0),
            'total_revenue' => (float) ($result->total_revenue ?? 0),
        ];
    }

    /**
     * Get today's stats (always from call_records for real-time data).
     */
    public function getTodayStats(User $user): array
    {
        $today = now()->startOfDay();

        return $this->getStatsFromCallRecords($user, $today, now());
    }

    /**
     * Get recent calls for dashboard.
     */
    public function getRecentCalls(User $user, int $limit = 10): \Illuminate\Support\Collection
    {
        return $this->scopedQuery($user, now()->subDays(7), now())
            ->with(['user:id,name', 'trunk:id,name'])
            ->orderByDesc('call_start')
            ->limit($limit)
            ->get();
    }

    /**
     * Find a call record by UUID with partition hint.
     */
    public function findByUuid(User $user, string $uuid, ?string $dateHint = null): ?CallRecord
    {
        $query = CallRecord::where('uuid', $uuid);

        // Use date hint for partition pruning if provided
        if ($dateHint) {
            $date = Carbon::parse($dateHint);
            $query->whereDate('call_start', $date);
        } else {
            // Fallback: search last 90 days
            $query->where('call_start', '>=', now()->subDays(90));
        }

        // Scope check
        if (!$user->isSuperAdmin()) {
            $userIds = $user->descendantIds();
            $query->whereIn('user_id', $userIds);
        }

        return $query->first();
    }

    /**
     * Clear CDR-related caches for a user.
     */
    public function clearCache(User $user): void
    {
        // Clear stats caches for common date ranges
        $patterns = [
            "cdr_stats_{$user->id}_*",
        ];

        // Note: Redis supports pattern-based deletion, file cache doesn't
        // For now, we rely on cache TTL expiration
    }
}

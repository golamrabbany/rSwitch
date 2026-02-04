<?php

namespace App\Services;

use App\Models\TrunkRoute;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RouteSelectionService
{
    /**
     * Find all matching trunk routes for a given destination number.
     *
     * Algorithm: longest prefix match + time window filter + priority/weight sort.
     */
    public function findRoutes(string $destinationNumber, ?Carbon $atTime = null): Collection
    {
        $atTime ??= now();

        // Generate all possible prefixes from longest to shortest
        $prefixes = [];
        $len = min(strlen($destinationNumber), 20);
        for ($i = $len; $i >= 1; $i--) {
            $prefixes[] = substr($destinationNumber, 0, $i);
        }

        if (empty($prefixes)) {
            return collect();
        }

        // Query matching routes with active trunks
        $routes = TrunkRoute::with('trunk')
            ->whereIn('prefix', $prefixes)
            ->where('status', 'active')
            ->whereHas('trunk', function ($q) {
                $q->whereIn('direction', ['outgoing', 'both'])
                  ->where('status', 'active')
                  ->where('health_status', '!=', 'down');
            })
            ->get();

        // Filter by time window and day of week
        $routes = $routes->filter(function (TrunkRoute $route) use ($atTime) {
            return $this->matchesTimeWindow($route, $atTime);
        });

        // Sort: longest prefix first → priority ASC → weight DESC
        return $routes->sort(function ($a, $b) {
            // Longest prefix first
            $prefixCmp = strlen($b->prefix) <=> strlen($a->prefix);
            if ($prefixCmp !== 0) return $prefixCmp;

            // Lower priority = preferred
            $priCmp = $a->priority <=> $b->priority;
            if ($priCmp !== 0) return $priCmp;

            // Higher weight = preferred
            return $b->weight <=> $a->weight;
        })->values();
    }

    /**
     * Select primary and failover trunks for a destination.
     *
     * @return array{primary: ?TrunkRoute, failover: ?TrunkRoute, all: Collection}
     */
    public function selectTrunks(string $destinationNumber, ?Carbon $atTime = null): array
    {
        $all = $this->findRoutes($destinationNumber, $atTime);

        if ($all->isEmpty()) {
            return ['primary' => null, 'failover' => null, 'all' => $all];
        }

        // Take only routes matching the longest prefix
        $longestPrefix = $all->first()->prefix;
        $matched = $all->filter(fn (TrunkRoute $r) => $r->prefix === $longestPrefix)->values();

        $primary = $matched->first();

        // Failover: next route with a different trunk_id
        $failover = $matched->first(function (TrunkRoute $r) use ($primary) {
            return $r->trunk_id !== $primary->trunk_id;
        });

        return [
            'primary' => $primary,
            'failover' => $failover,
            'all' => $matched,
        ];
    }

    /**
     * Check if a route's time window matches the given time.
     */
    protected function matchesTimeWindow(TrunkRoute $route, Carbon $atTime): bool
    {
        // No time restriction = always active
        if (is_null($route->time_start) && is_null($route->time_end)) {
            return true;
        }

        // Convert evaluation time to route's timezone
        $localTime = $atTime->copy()->setTimezone($route->timezone ?: 'UTC');

        // Check day of week
        if ($route->days_of_week) {
            $dayName = strtolower($localTime->format('D')); // mon, tue, wed, ...
            $allowedDays = array_map('trim', explode(',', $route->days_of_week));
            if (!in_array($dayName, $allowedDays)) {
                return false;
            }
        }

        $timeOfDay = $localTime->format('H:i:s');
        $start = $route->time_start;
        $end = $route->time_end;

        if ($start <= $end) {
            // Normal window: e.g. 06:00:00 to 18:00:00
            return $timeOfDay >= $start && $timeOfDay < $end;
        }

        // Overnight window: e.g. 22:00:00 to 06:00:00
        return $timeOfDay >= $start || $timeOfDay < $end;
    }
}

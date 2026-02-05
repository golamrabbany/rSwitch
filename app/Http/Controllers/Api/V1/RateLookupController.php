<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RateLookupController extends Controller
{
    public function __construct(
        private RatingService $ratingService,
    ) {}

    /**
     * GET /api/v1/rates/lookup?destination=447... — Look up rate for a destination.
     */
    public function lookup(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'destination' => ['required', 'string', 'max:30'],
        ]);

        if (!$user->rate_group_id) {
            return response()->json([
                'message' => 'No rate group assigned to your account.',
            ], 404);
        }

        $rate = $this->ratingService->findRate($request->destination, $user->rate_group_id);

        if (!$rate) {
            return response()->json([
                'message' => 'No rate found for this destination.',
                'destination' => $request->destination,
            ], 404);
        }

        // Estimate costs for common durations
        $estimates = [];

        foreach ([60, 180, 300, 600] as $seconds) {
            $calc = $this->ratingService->calculateCost($seconds, $rate);
            $estimates[] = [
                'duration_seconds' => $seconds,
                'billable_seconds' => $calc['billable_duration'],
                'cost' => $calc['total_cost'],
            ];
        }

        return response()->json([
            'destination' => $request->destination,
            'prefix' => $rate->prefix,
            'rate_per_minute' => $rate->rate_per_minute,
            'connection_fee' => $rate->connection_fee,
            'min_duration' => $rate->min_duration,
            'billing_increment' => $rate->billing_increment,
            'destination_name' => $rate->destination,
            'estimates' => $estimates,
        ]);
    }
}

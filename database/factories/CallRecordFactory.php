<?php

namespace Database\Factories;

use App\Models\CallRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<CallRecord> */
class CallRecordFactory extends Factory
{
    protected $model = CallRecord::class;

    public function definition(): array
    {
        $callStart = now()->subHours(fake()->numberBetween(1, 48));
        $duration = fake()->numberBetween(0, 600);

        return [
            'uuid' => Str::uuid()->toString(),
            'user_id' => User::factory(),
            'call_flow' => 'outbound',
            'caller' => fake()->numerify('1##########'),
            'callee' => fake()->numerify('44##########'),
            'call_start' => $callStart,
            'call_end' => $callStart->copy()->addSeconds($duration),
            'duration' => $duration,
            'billsec' => $duration > 0 ? $duration - fake()->numberBetween(0, 3) : 0,
            'billable_duration' => 0,
            'total_cost' => '0.0000',
            'reseller_cost' => '0.0000',
            'disposition' => 'ANSWERED',
            'status' => 'in_progress',
        ];
    }

    public function answered(): static
    {
        return $this->state(function () {
            $duration = fake()->numberBetween(10, 600);
            return [
                'disposition' => 'ANSWERED',
                'duration' => $duration,
                'billsec' => $duration,
            ];
        });
    }

    public function rated(): static
    {
        return $this->state(fn () => [
            'status' => 'rated',
            'rated_at' => now(),
            'total_cost' => fake()->randomFloat(4, 0.01, 5.00),
        ]);
    }

    public function unanswered(): static
    {
        return $this->state(fn () => [
            'disposition' => 'NO ANSWER',
            'duration' => 0,
            'billsec' => 0,
            'status' => 'unbillable',
        ]);
    }

    public function outbound(): static
    {
        return $this->state(fn () => ['call_flow' => 'outbound']);
    }

    public function inbound(): static
    {
        return $this->state(fn () => ['call_flow' => 'inbound']);
    }
}

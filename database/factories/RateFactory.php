<?php

namespace Database\Factories;

use App\Models\Rate;
use App\Models\RateGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Rate> */
class RateFactory extends Factory
{
    protected $model = Rate::class;

    public function definition(): array
    {
        return [
            'rate_group_id' => RateGroup::factory(),
            'prefix' => fake()->numerify('1###'),
            'destination' => fake()->country(),
            'rate_per_minute' => fake()->randomFloat(6, 0.001, 0.500),
            'connection_fee' => '0.000000',
            'min_duration' => 0,
            'billing_increment' => 6,
            'effective_date' => now()->subMonth()->toDateString(),
            'end_date' => null,
            'status' => 'active',
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['status' => 'disabled']);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'effective_date' => now()->subYear()->toDateString(),
            'end_date' => now()->subMonth()->toDateString(),
        ]);
    }
}

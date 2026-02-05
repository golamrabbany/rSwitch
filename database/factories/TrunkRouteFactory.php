<?php

namespace Database\Factories;

use App\Models\Trunk;
use App\Models\TrunkRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TrunkRoute> */
class TrunkRouteFactory extends Factory
{
    protected $model = TrunkRoute::class;

    public function definition(): array
    {
        return [
            'trunk_id' => Trunk::factory(),
            'prefix' => fake()->numerify('1###'),
            'priority' => 1,
            'weight' => 100,
            'status' => 'active',
        ];
    }

    public function withTimeWindow(): static
    {
        return $this->state(fn () => [
            'time_start' => '09:00:00',
            'time_end' => '17:00:00',
            'timezone' => 'UTC',
        ]);
    }
}

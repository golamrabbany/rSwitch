<?php

namespace Database\Factories;

use App\Models\Did;
use App\Models\Trunk;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Did> */
class DidFactory extends Factory
{
    protected $model = Did::class;

    public function definition(): array
    {
        return [
            'number' => '+1' . fake()->unique()->numerify('##########'),
            'provider' => fake()->company(),
            'trunk_id' => Trunk::factory()->incoming(),
            'destination_type' => 'sip_account',
            'monthly_cost' => fake()->randomFloat(4, 0.50, 5.00),
            'monthly_price' => fake()->randomFloat(4, 1.00, 10.00),
            'status' => 'unassigned',
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function assigned(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }
}

<?php

namespace Database\Factories;

use App\Models\RateGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RateGroup> */
class RateGroupFactory extends Factory
{
    protected $model = RateGroup::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true) . ' Rates',
            'description' => fake()->sentence(),
            'type' => 'admin',
            'created_by' => User::factory()->admin(),
        ];
    }

    public function reseller(): static
    {
        return $this->state(fn () => ['type' => 'reseller']);
    }
}

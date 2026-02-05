<?php

namespace Database\Factories;

use App\Models\RingGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class RingGroupFactory extends Factory
{
    protected $model = RingGroup::class;

    public function definition(): array
    {
        return [
            'name'         => fake()->words(2, true) . ' Team',
            'description'  => fake()->optional()->sentence(),
            'strategy'     => fake()->randomElement(['simultaneous', 'sequential', 'random']),
            'ring_timeout' => fake()->randomElement([20, 30, 45, 60]),
            'user_id'      => null,
            'status'       => 'active',
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['status' => 'disabled']);
    }

    public function simultaneous(): static
    {
        return $this->state(fn () => ['strategy' => 'simultaneous']);
    }

    public function sequential(): static
    {
        return $this->state(fn () => ['strategy' => 'sequential']);
    }
}

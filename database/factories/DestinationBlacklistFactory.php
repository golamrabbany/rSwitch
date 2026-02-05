<?php

namespace Database\Factories;

use App\Models\DestinationBlacklist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DestinationBlacklist> */
class DestinationBlacklistFactory extends Factory
{
    protected $model = DestinationBlacklist::class;

    public function definition(): array
    {
        return [
            'prefix' => fake()->numerify('900#'),
            'description' => 'Premium rate numbers',
            'applies_to' => 'all',
            'created_by' => User::factory()->admin(),
            'created_at' => now(),
        ];
    }

    public function forUser(): static
    {
        return $this->state(fn () => ['applies_to' => 'specific_users']);
    }
}

<?php

namespace Database\Factories;

use App\Models\DestinationWhitelist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DestinationWhitelist> */
class DestinationWhitelistFactory extends Factory
{
    protected $model = DestinationWhitelist::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'prefix' => fake()->numerify('1###'),
            'description' => 'Allowed destination',
            'created_by' => User::factory()->admin(),
            'created_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\KycProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KycProfile> */
class KycProfileFactory extends Factory
{
    protected $model = KycProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_type' => fake()->randomElement(['individual', 'company']),
            'full_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'address_line1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'country' => fake()->countryCode(),
            'id_type' => 'national_id',
            'id_number' => fake()->numerify('############'),
            'submitted_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'client',
            'status' => 'active',
            'kyc_status' => 'approved',
            'billing_type' => 'prepaid',
            'balance' => '100.0000',
            'credit_limit' => '0.0000',
            'currency' => 'USD',
            'max_channels' => 10,
            'low_balance_threshold' => '5.0000',
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin', 'kyc_status' => 'approved']);
    }

    public function reseller(): static
    {
        return $this->state(fn () => ['role' => 'reseller', 'balance' => '500.0000']);
    }

    public function client(): static
    {
        return $this->state(fn () => ['role' => 'client']);
    }

    public function prepaid(): static
    {
        return $this->state(fn () => ['billing_type' => 'prepaid']);
    }

    public function postpaid(): static
    {
        return $this->state(fn () => ['billing_type' => 'postpaid', 'credit_limit' => '1000.0000']);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => 'suspended']);
    }

    public function withBalance(string $amount): static
    {
        return $this->state(fn () => ['balance' => $amount]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Transaction> */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'topup',
            'amount' => fake()->randomFloat(4, 10, 500),
            'balance_after' => fake()->randomFloat(4, 10, 1000),
            'description' => 'Test transaction',
            'created_at' => now(),
        ];
    }

    public function callCharge(): static
    {
        return $this->state(fn () => [
            'type' => 'call_charge',
            'amount' => '-' . fake()->randomFloat(4, 0.01, 5.00),
        ]);
    }

    public function topup(): static
    {
        return $this->state(fn () => ['type' => 'topup']);
    }
}

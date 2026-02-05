<?php

namespace Database\Factories;

use App\Models\SipAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SipAccount> */
class SipAccountFactory extends Factory
{
    protected $model = SipAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'username' => fake()->unique()->numerify('1###'),
            'password' => Str::random(20),
            'auth_type' => 'password',
            'caller_id_name' => fake()->name(),
            'caller_id_number' => fake()->numerify('1##########'),
            'max_channels' => 2,
            'codec_allow' => 'ulaw,alaw,g729',
            'status' => 'active',
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => 'suspended']);
    }

    public function ipAuth(): static
    {
        return $this->state(fn () => [
            'auth_type' => 'ip',
            'allowed_ips' => fake()->ipv4(),
        ]);
    }
}

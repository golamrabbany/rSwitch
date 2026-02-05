<?php

namespace Database\Factories;

use App\Models\Trunk;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Trunk> */
class TrunkFactory extends Factory
{
    protected $model = Trunk::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Trunk',
            'provider' => fake()->company(),
            'direction' => 'outgoing',
            'host' => fake()->ipv4(),
            'port' => 5060,
            'username' => fake()->userName(),
            'password' => fake()->password(12),
            'register' => false,
            'transport' => 'udp',
            'codec_allow' => 'ulaw,alaw,g729',
            'max_channels' => 30,
            'outgoing_priority' => 1,
            'cli_mode' => 'passthrough',
            'incoming_context' => 'from-trunk',
            'incoming_auth_type' => 'ip',
            'health_check' => true,
            'health_status' => 'up',
            'health_fail_count' => 0,
            'health_auto_disable_threshold' => 5,
            'status' => 'active',
        ];
    }

    public function incoming(): static
    {
        return $this->state(fn () => ['direction' => 'incoming']);
    }

    public function both(): static
    {
        return $this->state(fn () => ['direction' => 'both']);
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['status' => 'disabled']);
    }

    public function down(): static
    {
        return $this->state(fn () => ['health_status' => 'down', 'health_fail_count' => 5]);
    }
}

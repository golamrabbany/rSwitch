<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<WebhookEndpoint> */
class WebhookEndpointFactory extends Factory
{
    protected $model = WebhookEndpoint::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'url' => fake()->url() . '/webhook',
            'secret' => Str::random(40),
            'events' => ['call.completed', 'payment.received'],
            'active' => true,
            'description' => fake()->sentence(),
            'failure_count' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }

    public function failing(): static
    {
        return $this->state(fn () => [
            'failure_count' => 8,
            'last_failed_at' => now(),
        ]);
    }

    public function autoDisabled(): static
    {
        return $this->state(fn () => [
            'active' => false,
            'failure_count' => 10,
            'last_failed_at' => now(),
        ]);
    }

    public function allEvents(): static
    {
        return $this->state(fn () => [
            'events' => array_keys(WebhookEndpoint::AVAILABLE_EVENTS),
        ]);
    }
}

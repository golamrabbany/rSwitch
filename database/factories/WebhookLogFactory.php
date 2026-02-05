<?php

namespace Database\Factories;

use App\Models\WebhookEndpoint;
use App\Models\WebhookLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WebhookLog> */
class WebhookLogFactory extends Factory
{
    protected $model = WebhookLog::class;

    public function definition(): array
    {
        return [
            'webhook_endpoint_id' => WebhookEndpoint::factory(),
            'event' => fake()->randomElement(array_keys(WebhookEndpoint::AVAILABLE_EVENTS)),
            'payload' => [
                'event' => 'call.completed',
                'timestamp' => now()->toIso8601String(),
                'data' => ['id' => fake()->randomNumber()],
            ],
            'response_code' => 200,
            'response_body' => 'OK',
            'response_time_ms' => fake()->numberBetween(50, 500),
            'status' => 'success',
            'attempt' => 1,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'response_code' => 500,
            'response_body' => 'Internal Server Error',
            'status' => 'failed',
            'error' => 'HTTP 500',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'response_code' => null,
            'response_body' => null,
            'response_time_ms' => null,
            'status' => 'pending',
        ]);
    }
}

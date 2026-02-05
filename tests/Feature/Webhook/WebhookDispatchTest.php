<?php

namespace Tests\Feature\Webhook;

use App\Jobs\SendWebhook;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Services\WebhookDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_to_matching_endpoints(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        WebhookEndpoint::factory()->create([
            'user_id' => $user->id,
            'events' => ['call.completed', 'payment.received'],
            'active' => true,
        ]);

        $count = WebhookDispatcher::dispatch('call.completed', ['test' => true], $user->id);

        $this->assertEquals(1, $count);
        Queue::assertPushed(SendWebhook::class, 1);
    }

    public function test_skips_inactive_endpoints(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        WebhookEndpoint::factory()->inactive()->create([
            'user_id' => $user->id,
            'events' => ['call.completed'],
        ]);

        $count = WebhookDispatcher::dispatch('call.completed', ['test' => true], $user->id);

        $this->assertEquals(0, $count);
        Queue::assertNotPushed(SendWebhook::class);
    }

    public function test_skips_endpoints_not_subscribed_to_event(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        WebhookEndpoint::factory()->create([
            'user_id' => $user->id,
            'events' => ['payment.received'],
            'active' => true,
        ]);

        $count = WebhookDispatcher::dispatch('call.completed', ['test' => true], $user->id);

        $this->assertEquals(0, $count);
        Queue::assertNotPushed(SendWebhook::class);
    }

    public function test_dispatches_to_admin_endpoints_for_any_user(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $client = User::factory()->client()->create();

        WebhookEndpoint::factory()->create([
            'user_id' => $admin->id,
            'events' => ['call.completed'],
            'active' => true,
        ]);

        $count = WebhookDispatcher::dispatch('call.completed', ['test' => true], $client->id);

        $this->assertEquals(1, $count);
        Queue::assertPushed(SendWebhook::class, 1);
    }

    public function test_dispatches_to_both_user_and_admin_endpoints(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $client = User::factory()->client()->create();

        WebhookEndpoint::factory()->create([
            'user_id' => $admin->id,
            'events' => ['payment.received'],
            'active' => true,
        ]);

        WebhookEndpoint::factory()->create([
            'user_id' => $client->id,
            'events' => ['payment.received'],
            'active' => true,
        ]);

        $count = WebhookDispatcher::dispatch('payment.received', ['amount' => 25], $client->id);

        $this->assertEquals(2, $count);
        Queue::assertPushed(SendWebhook::class, 2);
    }
}

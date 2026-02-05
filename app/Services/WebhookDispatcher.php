<?php

namespace App\Services;

use App\Jobs\SendWebhook;
use App\Models\WebhookEndpoint;

class WebhookDispatcher
{
    /**
     * Dispatch a webhook event to all matching active endpoints.
     */
    public static function dispatch(string $event, array $payload, ?int $userId = null): int
    {
        $query = WebhookEndpoint::active()->forEvent($event);

        if ($userId) {
            // Dispatch to user's own endpoints + admin endpoints
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereHas('user', fn ($u) => $u->where('role', 'admin'));
            });
        }

        $endpoints = $query->get();
        $count = 0;

        foreach ($endpoints as $endpoint) {
            SendWebhook::dispatch($endpoint, $event, $payload);
            $count++;
        }

        return $count;
    }
}

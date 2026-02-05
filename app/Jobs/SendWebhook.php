<?php

namespace App\Jobs;

use App\Models\WebhookEndpoint;
use App\Models\WebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300]; // 10s, 1min, 5min

    public function __construct(
        public WebhookEndpoint $endpoint,
        public string $event,
        public array $payload,
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        $body = [
            'event' => $this->event,
            'timestamp' => now()->toIso8601String(),
            'data' => $this->payload,
        ];

        $signature = hash_hmac('sha256', json_encode($body), $this->endpoint->secret);

        $log = WebhookLog::create([
            'webhook_endpoint_id' => $this->endpoint->id,
            'event' => $this->event,
            'payload' => $body,
            'status' => 'pending',
            'attempt' => $this->attempts(),
        ]);

        $startTime = microtime(true);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $this->event,
                    'User-Agent' => 'rSwitch-Webhook/1.0',
                ])
                ->post($this->endpoint->url, $body);

            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            $log->update([
                'response_code' => $response->status(),
                'response_body' => substr($response->body(), 0, 2000),
                'response_time_ms' => $responseTimeMs,
                'status' => $response->successful() ? 'success' : 'failed',
                'error' => $response->successful() ? null : "HTTP {$response->status()}",
            ]);

            if ($response->successful()) {
                $this->endpoint->update([
                    'failure_count' => 0,
                    'last_triggered_at' => now(),
                ]);
            } else {
                $this->markFailed($log, "HTTP {$response->status()}");
            }
        } catch (\Exception $e) {
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            $log->update([
                'response_time_ms' => $responseTimeMs,
                'status' => 'failed',
                'error' => substr($e->getMessage(), 0, 500),
            ]);

            $this->markFailed($log, $e->getMessage());

            throw $e; // Re-throw so the job retries
        }
    }

    private function markFailed(WebhookLog $log, string $error): void
    {
        $this->endpoint->increment('failure_count');
        $this->endpoint->update(['last_failed_at' => now()]);

        // Auto-disable after 10 consecutive failures
        if ($this->endpoint->failure_count >= 10) {
            $this->endpoint->update(['active' => false]);

            Log::warning('Webhook endpoint auto-disabled after 10 failures', [
                'endpoint_id' => $this->endpoint->id,
                'url' => $this->endpoint->url,
            ]);
        }
    }
}

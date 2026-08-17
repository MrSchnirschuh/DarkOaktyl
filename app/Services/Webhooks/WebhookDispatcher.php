<?php

namespace DarkOak\Services\Webhooks;

use DarkOak\Models\Webhook;
use DarkOak\Models\WebhookLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WebhookDispatcher
{
    /**
     * Maximum number of delivery attempts.
     */
    protected int $maxAttempts = 3;

    /**
     * Timeout in seconds for each request.
     */
    protected int $timeout = 10;

    /**
     * Dispatch a webhook event.
     */
    public function dispatch(Webhook $webhook, string $event, array $payload): bool
    {
        if (!$webhook->enabled || !$webhook->listensTo($event)) {
            return false;
        }

        return $this->deliver($webhook, $event, $payload);
    }

    /**
     * Deliver a webhook payload.
     */
    public function deliver(Webhook $webhook, string $event, array $payload, int $attempt = 1): bool
    {
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = $webhook->signPayload($jsonPayload);

        try {
            $response = Http::withHeaders([
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Event' => $event,
                'X-Webhook-ID' => $webhook->uuid,
                'Content-Type' => 'application/json',
                'User-Agent' => 'DarkOaktyl-Webhook/1.0',
            ])
                ->timeout($this->timeout)
                ->post($webhook->url, $payload);

            $success = $response->successful();

            $this->logDelivery($webhook, $event, $jsonPayload, $attempt, $success, $response);

            // Update webhook metadata
            $webhook->update([
                'last_response_code' => $response->status(),
                'last_sent_at' => now(),
            ]);

            return $success;
        } catch (\Exception $e) {
            $this->logDelivery($webhook, $event, $jsonPayload, $attempt, false, null, $e->getMessage());

            // Retry with exponential backoff
            if ($attempt < $this->maxAttempts) {
                $delay = $attempt * 60; // 1min, 2min, 3min
                Log::info("Webhook delivery failed, retrying in {$delay}s", [
                    'webhook' => $webhook->uuid,
                    'attempt' => $attempt,
                    'event' => $event,
                ]);

                // Queue retry via dispatch
                dispatch(function () use ($webhook, $event, $payload, $attempt) {
                    $this->deliver($webhook, $event, $payload, $attempt + 1);
                })->delay(now()->addSeconds($delay));
            }

            return false;
        }
    }

    /**
     * Send a test webhook event.
     */
    public function test(Webhook $webhook): bool
    {
        return $this->deliver($webhook, 'webhook.test', [
            'event' => 'webhook.test',
            'timestamp' => now()->toIso8601String(),
            'webhook_id' => $webhook->uuid,
            'test' => true,
        ]);
    }

    /**
     * Resend a failed webhook delivery.
     */
    public function resend(WebhookLog $log): bool
    {
        $webhook = $log->webhook;
        $payload = json_decode($log->payload, true);

        return $this->deliver($webhook, $log->event, $payload, $log->attempt + 1);
    }

    /**
     * Log a webhook delivery attempt.
     */
    protected function logDelivery(
        Webhook $webhook,
        string $event,
        string $payload,
        int $attempt,
        bool $success,
        ?\Illuminate\Http\Client\Response $response = null,
        ?string $error = null,
    ): WebhookLog {
        return WebhookLog::create([
            'webhook_id' => $webhook->id,
            'event' => $event,
            'payload' => $payload,
            'attempt' => $attempt,
            'response_code' => $response?->status(),
            'response_body' => $response?->body() ? substr($response->body(), 0, 10000) : null,
            'success' => $success,
            'error_message' => $error ? substr($error, 0, 500) : null,
        ]);
    }
}

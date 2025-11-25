<?php

namespace DarkOak\Services\Domains;

use DarkOak\Models\ServerDomain;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DomainDaemonSyncService
{
    public function requested(ServerDomain $domain): void
    {
        $this->notify('domain.requested', $domain);
    }

    public function provisioned(ServerDomain $domain): void
    {
        $this->notify('domain.provisioned', $domain);
    }

    public function failed(ServerDomain $domain, string $reason): void
    {
        $this->notify('domain.failed', $domain, ['reason' => $reason]);
    }

    protected function notify(string $event, ServerDomain $domain, array $extra = []): void
    {
        if (!config('domains.sync.enabled')) {
            return;
        }

        $endpoint = rtrim((string) config('domains.sync.endpoint'), '/');
        if (empty($endpoint)) {
            Log::warning('Domain sync is enabled but no endpoint is configured.');

            return;
        }

        $payload = array_merge([
            'event' => $event,
            'domain' => $this->serializeDomain($domain),
        ], $extra);

        $headers = [];
        if ($token = config('domains.sync.token')) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout((int) config('domains.sync.timeout', 10))
                ->post($endpoint, $payload);

            if ($response->failed()) {
                Log::warning('Domain sync endpoint responded with an error.', [
                    'event' => $event,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error('Unable to notify domain sync endpoint.', [
                'event' => $event,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function serializeDomain(ServerDomain $domain): array
    {
        $domain->loadMissing('root');

        return [
            'id' => $domain->id,
            'server_id' => $domain->server_id,
            'hostname' => $domain->hostname,
            'status' => $domain->status,
            'type' => $domain->type,
            'provider_payload' => $domain->provider_payload,
            'root' => $domain->root ? [
                'id' => $domain->root->id,
                'name' => $domain->root->name,
                'root_domain' => $domain->root->root_domain,
                'provider' => $domain->root->provider,
            ] : null,
        ];
    }
}

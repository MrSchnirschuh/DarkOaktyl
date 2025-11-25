<?php

namespace DarkOak\Services\Domains\Providers;

use DarkOak\Contracts\Domains\DomainProvider;
use DarkOak\Models\DomainRoot;
use DarkOak\Models\ServerDomain;
use DarkOak\Services\Domains\DTO\DomainProvisioningResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CloudflareDomainProvider implements DomainProvider
{
    public function provision(ServerDomain $domain, DomainRoot $root): DomainProvisioningResponse
    {
        $zoneId = (string) $root->providerOption('zone_id');
        $apiToken = (string) $root->providerOption('api_token');
        $recordType = Str::upper((string) ($root->providerOption('record_type', 'A')));
        $origin = (string) $root->providerOption($recordType === 'AAAA' ? 'origin_ipv6' : 'origin_ipv4');

        if (!$zoneId || !$apiToken) {
            throw new \RuntimeException('Cloudflare credentials are not configured for this domain root.');
        }

        if (!$origin) {
            throw new \RuntimeException('Cloudflare origin address is missing in provider configuration.');
        }

        $payload = [
            'type' => $recordType,
            'name' => $domain->hostname,
            'content' => $origin,
            'ttl' => (int) $root->providerOption('ttl', 120),
            'proxied' => (bool) $root->providerOption('proxied', true),
        ];

        $response = Http::withToken($apiToken)
            ->acceptJson()
            ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records", $payload);

        if (!$response->successful()) {
            $error = $response->json('errors', $response->body());
            throw new \RuntimeException('Cloudflare API error: ' . json_encode($error));
        }

        $result = $response->json('result', []);

        return new DomainProvisioningResponse(
            providerPayload: [
                'cloudflare' => [
                    'id' => Arr::get($result, 'id'),
                    'type' => Arr::get($result, 'type'),
                    'name' => Arr::get($result, 'name'),
                    'content' => Arr::get($result, 'content'),
                    'proxied' => Arr::get($result, 'proxied'),
                ],
            ],
            verified: true
        );
    }
}

<?php

namespace DarkOak\Services\Domains;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    private string $apiBase = 'https://api.cloudflare.com/client/v4';

    public function __construct(
        private string $apiToken,
        private string $zoneId,
    ) {}

    public static function fromConfig(array $config): self
    {
        return new self(
            apiToken: $config['api_token'] ?? '',
            zoneId: $config['zone_id'] ?? '',
        );
    }

    /**
     * Create a DNS A/AAAA record in Cloudflare.
     */
    public function createDnsRecord(string $subdomain, string $rootDomain, array $config): bool
    {
        $type = strtoupper($config['record_type'] ?? 'A');
        $name = $subdomain ? "$subdomain.$rootDomain" : $rootDomain;
        $content = $type === 'AAAA' ? ($config['origin_ipv6'] ?? '') : ($config['origin_ipv4'] ?? '');

        if (empty($content)) {
            throw new \RuntimeException("No IP address configured for $type record on $name");
        }

        return $this->request('POST', "/zones/{$this->zoneId}/dns_records", [
            'type' => $type,
            'name' => $name,
            'content' => $content,
            'ttl' => (int) ($config['ttl'] ?? 120),
            'proxied' => (bool) ($config['proxied'] ?? true),
        ]);
    }

    /**
     * Delete a DNS record by name and type.
     */
    public function deleteDnsRecord(string $subdomain, string $rootDomain, string $type = 'A'): bool
    {
        $name = $subdomain ? "$subdomain.$rootDomain" : $rootDomain;
        $records = $this->listDnsRecords($name, $type);

        foreach ($records as $record) {
            $this->request('DELETE', "/zones/{$this->zoneId}/dns_records/{$record['id']}");
        }

        return true;
    }

    /**
     * List DNS records matching a name and type.
     */
    public function listDnsRecords(string $name, string $type = 'A'): array
    {
        $response = $this->request('GET', "/zones/{$this->zoneId}/dns_records", [
            'name' => $name,
            'type' => $type,
            'per_page' => 50,
        ]);

        return $response['result'] ?? [];
    }

    /**
     * Verify the API token works and the zone exists.
     */
    public function verifyCredentials(): bool
    {
        if (empty($this->apiToken) || empty($this->zoneId)) {
            return false;
        }

        try {
            $resp = $this->request('GET', "/zones/{$this->zoneId}");
            return ($resp['success'] ?? false) && ($resp['result']['id'] ?? null) === $this->zoneId;
        } catch (\Exception $e) {
            Log::warning('Cloudflare credential verification failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function request(string $method, string $path, array $body = []): array
    {
        $url = $this->apiBase . $path;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
            'Content-Type' => 'application/json',
        ])->send($method, $url, $method === 'GET' ? ['query' => $body] : ['json' => $body]);

        $data = $response->json() ?? [];

        if (!$response->successful() || !($data['success'] ?? false)) {
            $errors = $data['errors'] ?? [['message' => 'Unknown Cloudflare API error']];
            $msg = $errors[0]['message'] ?? 'Unknown error';
            Log::error('Cloudflare API error', [
                'path' => $path,
                'method' => $method,
                'status' => $response->status(),
                'errors' => $errors,
            ]);
            throw new \RuntimeException("Cloudflare API error: $msg");
        }

        return $data;
    }
}
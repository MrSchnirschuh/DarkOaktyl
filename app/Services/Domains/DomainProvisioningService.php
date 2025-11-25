<?php

namespace DarkOak\Services\Domains;

use DarkOak\Events\Domain\ServerDomainProvisionRequested;
use DarkOak\Exceptions\DisplayException;
use DarkOak\Jobs\Domains\ProvisionServerDomain;
use DarkOak\Models\DomainRoot;
use DarkOak\Models\Server;
use DarkOak\Models\ServerDomain;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DomainProvisioningService
{
    public function requestProvision(Server $server, ?array $domainRequest): ?ServerDomain
    {
        if (empty($domainRequest)) {
            return null;
        }

        $rootId = (int) Arr::get($domainRequest, 'root_id');
        if (!$rootId) {
            return null;
        }

        /** @var DomainRoot|null $root */
        $root = DomainRoot::query()->whereKey($rootId)->where('is_active', true)->first();
        if (!$root) {
            throw new DisplayException('The selected root domain is no longer available.');
        }

        $subdomain = $this->prepareSubdomain(Arr::get($domainRequest, 'subdomain'), $server->uuidShort);
        $hostname = $this->buildHostname($subdomain, $root->root_domain);

        $existing = ServerDomain::query()->where('hostname', $hostname)->first();
        if ($existing) {
            throw new DisplayException('The requested hostname is already in use.');
        }

        $serverDomain = ServerDomain::query()->create([
            'server_id' => $server->id,
            'domain_root_id' => $root->id,
            'type' => Arr::get($domainRequest, 'type', 'managed'),
            'hostname' => $hostname,
            'subdomain' => $subdomain,
            'status' => ServerDomain::STATUS_PENDING,
            'verification_method' => Arr::get($domainRequest, 'verification_method'),
            'verification_token' => Arr::get($domainRequest, 'verification_token'),
        ]);

        event(new ServerDomainProvisionRequested($serverDomain));
        ProvisionServerDomain::dispatch($serverDomain->id);

        return $serverDomain;
    }

    private function prepareSubdomain(?string $requested, string $fallback): string
    {
        $value = $requested ?: $fallback;
        $normalized = Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9-]/', '-')
            ->trim('-')
            ->limit(63, '');

        return (string) ($normalized->isEmpty() ? Str::lower($fallback) : $normalized);
    }

    private function buildHostname(string $subdomain, string $rootDomain): string
    {
        return sprintf('%s.%s', $subdomain, $rootDomain);
    }
}

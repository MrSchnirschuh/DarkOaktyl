<?php

namespace DarkOak\Services\Domains;

use DarkOak\Contracts\Domains\DomainProvider;
use DarkOak\Models\DomainRoot;
use DarkOak\Services\Domains\Providers\CloudflareDomainProvider;

class DomainProviderResolver
{
    public function __construct(private CloudflareDomainProvider $cloudflareProvider)
    {
    }

    public function resolve(?DomainRoot $root): ?DomainProvider
    {
        if (!$root) {
            return null;
        }

        return match ($root->provider) {
            'cloudflare' => $this->cloudflareProvider,
            default => null,
        };
    }
}

<?php

namespace DarkOak\Jobs\Domains;

use Carbon\CarbonImmutable;
use DarkOak\Events\Domain\ServerDomainProvisionFailed;
use DarkOak\Events\Domain\ServerDomainProvisioned;
use DarkOak\Jobs\Job;
use DarkOak\Models\ServerDomain;
use DarkOak\Services\Domains\DomainProviderResolver;
use DarkOak\Services\Domains\DTO\DomainProvisioningResponse;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;

class ProvisionServerDomain extends Job implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    public function __construct(public int $serverDomainId)
    {
        $this->queue = 'domains';
    }

    public function handle(DomainProviderResolver $resolver): void
    {
        /** @var ServerDomain $domain */
        $domain = ServerDomain::query()->with('root')->findOrFail($this->serverDomainId);
        $root = $domain->root;

        if (!$root || !$root->is_active) {
            $this->markFailed($domain, 'Root domain is disabled.');

            return;
        }

        $provider = $resolver->resolve($root);

        if (!$provider) {
            $domain->forceFill([
                'status' => ServerDomain::STATUS_ACTIVE,
                'last_synced_at' => CarbonImmutable::now(),
            ])->save();

            event(new ServerDomainProvisioned($domain));

            return;
        }

        $domain->update(['status' => ServerDomain::STATUS_PROVISIONING]);

        try {
            $result = $provider->provision($domain, $root);
            $this->markProvisioned($domain, $result);
        } catch (\Throwable $exception) {
            $this->markFailed($domain, $exception->getMessage());

            throw $exception;
        }
    }

    private function markProvisioned(ServerDomain $domain, DomainProvisioningResponse $response): void
    {
        $domain->forceFill([
            'status' => ServerDomain::STATUS_ACTIVE,
            'provider_payload' => $response->providerPayload,
            'verified_at' => $response->verified ? $response->verifiedAt : null,
            'last_synced_at' => $response->syncedAt ?? CarbonImmutable::now(),
        ])->save();

        event(new ServerDomainProvisioned($domain));
    }

    private function markFailed(ServerDomain $domain, string $reason): void
    {
        $payload = $domain->provider_payload ?? [];
        $payload['last_error'] = $reason;

        $domain->forceFill([
            'status' => ServerDomain::STATUS_ERROR,
            'provider_payload' => $payload,
            'last_synced_at' => CarbonImmutable::now(),
        ])->save();

        event(new ServerDomainProvisionFailed($domain, $reason));
    }
}

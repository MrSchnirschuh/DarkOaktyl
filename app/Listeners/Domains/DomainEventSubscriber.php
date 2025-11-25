<?php

namespace DarkOak\Listeners\Domains;

use DarkOak\Events\Domain\ServerDomainProvisionFailed;
use DarkOak\Events\Domain\ServerDomainProvisionRequested;
use DarkOak\Events\Domain\ServerDomainProvisioned;
use DarkOak\Services\Domains\DomainDaemonSyncService;
use Illuminate\Events\Dispatcher;

class DomainEventSubscriber
{
    public function __construct(private DomainDaemonSyncService $sync)
    {
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(ServerDomainProvisionRequested::class, [$this, 'handleRequested']);
        $events->listen(ServerDomainProvisioned::class, [$this, 'handleProvisioned']);
        $events->listen(ServerDomainProvisionFailed::class, [$this, 'handleFailed']);
    }

    public function handleRequested(ServerDomainProvisionRequested $event): void
    {
        $this->sync->requested($event->domain);
    }

    public function handleProvisioned(ServerDomainProvisioned $event): void
    {
        $this->sync->provisioned($event->domain);
    }

    public function handleFailed(ServerDomainProvisionFailed $event): void
    {
        $this->sync->failed($event->domain, $event->reason);
    }
}

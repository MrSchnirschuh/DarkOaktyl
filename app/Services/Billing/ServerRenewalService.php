<?php

namespace DarkOak\Services\Billing;

use DarkOak\Models\Server;
use DarkOak\Services\Servers\SuspensionService;

class ServerRenewalService
{
    public function __construct(private SuspensionService $suspensionService)
    {
    }

    /**
     * Process the renewal of an existing billable server.
     */
    public function handle(Server $server): Server
    {
        if ($server->isSuspended()) {
            $this->suspensionService->toggle($server, SuspensionService::ACTION_UNSUSPEND);
        }

        $date = $server->renewal_date
            ->addDays(config('modules.billing.renewal.days'))
            ->toDateTimeString();

        $server->update(['renewal_date' => $date]);

        return $server;
    }
}

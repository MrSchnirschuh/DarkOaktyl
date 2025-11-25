<?php

namespace DarkOak\Events\Domain;

use DarkOak\Events\Event;
use DarkOak\Models\ServerDomain;
use Illuminate\Queue\SerializesModels;

class ServerDomainProvisionFailed extends Event
{
    use SerializesModels;

    public function __construct(public ServerDomain $domain, public string $reason)
    {
    }
}

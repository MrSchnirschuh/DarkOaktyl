<?php

namespace DarkOak\Events\Domain;

use DarkOak\Events\Event;
use DarkOak\Models\ServerDomain;
use Illuminate\Queue\SerializesModels;

class ServerDomainProvisioned extends Event
{
    use SerializesModels;

    public function __construct(public ServerDomain $domain)
    {
    }
}

<?php

namespace DarkOak\Events\Server;

use DarkOak\Events\Event;
use DarkOak\Models\Server;
use Illuminate\Queue\SerializesModels;

class Creating extends Event
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Server $server)
    {
    }
}


<?php

namespace DarkOak\Events\Subuser;

use DarkOak\Events\Event;
use DarkOak\Models\Subuser;
use Illuminate\Queue\SerializesModels;

class Created extends Event
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Subuser $subuser)
    {
    }
}


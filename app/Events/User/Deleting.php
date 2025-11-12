<?php

namespace DarkOak\Events\User;

use DarkOak\Models\User;
use DarkOak\Events\Event;
use Illuminate\Queue\SerializesModels;

class Deleting extends Event
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public User $user)
    {
    }
}


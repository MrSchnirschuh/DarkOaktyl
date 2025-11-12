<?php

namespace DarkOak\Events\Auth;

use DarkOak\Models\User;
use DarkOak\Events\Event;

class ProvidedAuthenticationToken extends Event
{
    public function __construct(public User $user, public bool $recovery = false)
    {
    }
}


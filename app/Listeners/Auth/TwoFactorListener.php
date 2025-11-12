<?php

namespace DarkOak\Listeners\Auth;

use DarkOak\Facades\Activity;
use DarkOak\Events\Auth\ProvidedAuthenticationToken;

class TwoFactorListener
{
    public function handle(ProvidedAuthenticationToken $event): void
    {
        Activity::event($event->recovery ? 'auth:recovery-token' : 'auth:token')
            ->withRequestMetadata()
            ->subject($event->user)
            ->log();
    }
}


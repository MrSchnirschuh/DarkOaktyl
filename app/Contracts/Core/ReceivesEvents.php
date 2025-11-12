<?php

namespace DarkOak\Contracts\Core;

use DarkOak\Events\Event;

interface ReceivesEvents
{
    /**
     * Handles receiving an event from the application.
     */
    public function handle(Event $notification): void;
}


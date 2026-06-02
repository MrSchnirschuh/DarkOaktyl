<?php

namespace DarkOak\Http\Requests\Api\Application\Tickets;

use DarkOak\Models\AdminRole;
use DarkOak\Models\TicketMessage;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreTicketMessageRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return TicketMessage::rules();
    }

    public function permission(): string
    {
        return AdminRole::TICKETS_MESSAGE;
    }
}

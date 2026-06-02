<?php

namespace DarkOak\Http\Requests\Api\Application\Tickets;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteTicketRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::TICKETS_DELETE;
    }
}

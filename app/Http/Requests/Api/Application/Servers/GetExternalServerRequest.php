<?php

namespace DarkOak\Http\Requests\Api\Application\Servers;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class GetExternalServerRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::SERVERS_READ;
    }
}


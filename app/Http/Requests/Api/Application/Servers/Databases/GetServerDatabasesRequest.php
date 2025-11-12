<?php

namespace DarkOak\Http\Requests\Api\Application\Servers\Databases;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class GetServerDatabasesRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::SERVERS_READ;
    }
}


<?php

namespace DarkOak\Http\Requests\Api\Application\Servers\Databases;

use DarkOak\Models\AdminRole;

class ServerDatabaseWriteRequest extends GetServerDatabasesRequest
{
    public function permission(): string
    {
        return AdminRole::SERVERS_UPDATE;
    }
}


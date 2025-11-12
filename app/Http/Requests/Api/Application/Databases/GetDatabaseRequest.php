<?php

namespace DarkOak\Http\Requests\Api\Application\Databases;

use DarkOak\Models\AdminRole;

class GetDatabaseRequest extends GetDatabasesRequest
{
    public function permission(): string
    {
        return AdminRole::DATABASES_READ;
    }
}


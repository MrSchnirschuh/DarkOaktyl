<?php

namespace DarkOak\Http\Requests\Api\Client\Servers\Databases;

use DarkOak\Models\Permission;
use DarkOak\Contracts\Http\ClientPermissionsRequest;
use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class DeleteDatabaseRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permission::ACTION_DATABASE_DELETE;
    }
}


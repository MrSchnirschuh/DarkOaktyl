<?php

namespace DarkOak\Http\Requests\Api\Client\Servers\Network;

use DarkOak\Models\Permission;
use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class DeleteAllocationRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return Permission::ACTION_ALLOCATION_DELETE;
    }
}


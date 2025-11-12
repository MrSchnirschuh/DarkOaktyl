<?php

namespace DarkOak\Http\Requests\Api\Client\Servers\Startup;

use DarkOak\Models\Permission;
use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class GetStartupRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return Permission::ACTION_STARTUP_READ;
    }
}


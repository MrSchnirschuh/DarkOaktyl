<?php

namespace DarkOak\Http\Requests\Api\Client\Servers\Settings;

use DarkOak\Models\Permission;
use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class ReinstallServerRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return Permission::ACTION_SETTINGS_REINSTALL;
    }
}


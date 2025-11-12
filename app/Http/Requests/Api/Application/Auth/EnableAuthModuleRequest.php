<?php

namespace DarkOak\Http\Requests\Api\Application\Auth;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class EnableAuthModuleRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::AUTH_CREATE;
    }
}


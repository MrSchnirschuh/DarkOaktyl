<?php

namespace DarkOak\Http\Requests\Api\Application\Roles;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class GetRolesRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::ROLES_READ;
    }
}


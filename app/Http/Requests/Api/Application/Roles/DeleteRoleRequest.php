<?php

namespace DarkOak\Http\Requests\Api\Application\Roles;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteRoleRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::ROLES_DELETE;
    }
}


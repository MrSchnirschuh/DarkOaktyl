<?php

namespace DarkOak\Http\Requests\Api\Application\Roles;

use DarkOak\Models\AdminRole;

class GetRoleRequest extends GetRolesRequest
{
    public function permission(): string
    {
        return AdminRole::ROLES_READ;
    }
}


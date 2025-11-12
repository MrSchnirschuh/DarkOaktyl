<?php

namespace DarkOak\Http\Requests\Api\Application\Roles;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreRoleRequest extends ApplicationApiRequest
{
    public function rules(array $rules = null): array
    {
        return $rules ?? AdminRole::getRules();
    }

    public function permission(): string
    {
        return AdminRole::ROLES_CREATE;
    }
}


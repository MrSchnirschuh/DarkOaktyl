<?php

namespace DarkOak\Http\Requests\Api\Application\Users;

use DarkOak\Models\User;
use DarkOak\Models\AdminRole;

class UpdateUserRequest extends StoreUserRequest
{
    public function rules(array $rules = null): array
    {
        return parent::rules($rules ?? User::getRulesForUpdate($this->route()->parameter('user')));
    }

    public function permission(): string
    {
        return AdminRole::USERS_UPDATE;
    }
}


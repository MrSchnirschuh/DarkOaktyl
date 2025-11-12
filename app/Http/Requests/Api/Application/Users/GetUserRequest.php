<?php

namespace DarkOak\Http\Requests\Api\Application\Users;

use DarkOak\Models\AdminRole;

class GetUserRequest extends GetUsersRequest
{
    public function permission(): string
    {
        return AdminRole::USERS_READ;
    }
}


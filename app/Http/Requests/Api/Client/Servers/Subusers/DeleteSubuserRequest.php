<?php

namespace DarkOak\Http\Requests\Api\Client\Servers\Subusers;

use DarkOak\Models\Permission;

class DeleteSubuserRequest extends SubuserRequest
{
    public function permission(): string
    {
        return Permission::ACTION_USER_DELETE;
    }
}


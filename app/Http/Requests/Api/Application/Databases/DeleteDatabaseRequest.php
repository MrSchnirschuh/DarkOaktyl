<?php

namespace DarkOak\Http\Requests\Api\Application\Databases;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteDatabaseRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::DATABASES_DELETE;
    }
}


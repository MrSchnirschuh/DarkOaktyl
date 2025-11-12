<?php

namespace DarkOak\Http\Requests\Api\Application\Databases;

use DarkOak\Models\AdminRole;
use DarkOak\Models\DatabaseHost;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreDatabaseRequest extends ApplicationApiRequest
{
    public function rules(array $rules = null): array
    {
        return $rules ?? DatabaseHost::getRules();
    }

    public function permission(): string
    {
        return AdminRole::DATABASES_CREATE;
    }
}


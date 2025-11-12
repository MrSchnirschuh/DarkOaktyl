<?php

namespace DarkOak\Http\Requests\Api\Application\Databases;

use DarkOak\Models\AdminRole;
use DarkOak\Models\DatabaseHost;

class UpdateDatabaseRequest extends StoreDatabaseRequest
{
    public function rules(array $rules = null): array
    {
        return $rules ?? DatabaseHost::getRulesForUpdate($this->route()->parameter('databaseHost'));
    }

    public function permission(): string
    {
        return AdminRole::DATABASES_UPDATE;
    }
}


<?php

namespace DarkOak\Http\Requests\Api\Application\Nests;

use DarkOak\Models\Nest;
use DarkOak\Models\AdminRole;

class UpdateNestRequest extends StoreNestRequest
{
    public function rules(array $rules = null): array
    {
        return $rules ?? Nest::getRulesForUpdate($this->route()->parameter('nest'));
    }

    public function permission(): string
    {
        return AdminRole::NESTS_UPDATE;
    }
}


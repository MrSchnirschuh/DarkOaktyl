<?php

namespace DarkOak\Http\Requests\Api\Application\Nests;

use DarkOak\Models\Nest;
use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreNestRequest extends ApplicationApiRequest
{
    public function rules(array $rules = null): array
    {
        return $rules ?? Nest::getRules();
    }

    public function permission(): string
    {
        return AdminRole::NESTS_CREATE;
    }
}


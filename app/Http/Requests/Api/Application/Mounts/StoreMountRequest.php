<?php

namespace DarkOak\Http\Requests\Api\Application\Mounts;

use DarkOak\Models\Mount;
use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreMountRequest extends ApplicationApiRequest
{
    public function rules(array $rules = null): array
    {
        return $rules ?? Mount::getRules();
    }

    public function permission(): string
    {
        return AdminRole::MOUNTS_CREATE;
    }
}


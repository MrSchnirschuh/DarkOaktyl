<?php

namespace DarkOak\Http\Requests\Api\Application\Eggs\Variables;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteEggVariableRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::EGGS_DELETE;
    }
}

<?php

namespace DarkOak\Http\Requests\Api\Application\Eggs;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class GetEggsRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::EGGS_READ;
    }
}


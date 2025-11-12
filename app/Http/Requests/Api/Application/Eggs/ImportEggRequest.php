<?php

namespace DarkOak\Http\Requests\Api\Application\Eggs;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class ImportEggRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::EGGS_IMPORT;
    }
}


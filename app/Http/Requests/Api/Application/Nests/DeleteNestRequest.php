<?php

namespace DarkOak\Http\Requests\Api\Application\Nests;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteNestRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::NESTS_DELETE;
    }
}


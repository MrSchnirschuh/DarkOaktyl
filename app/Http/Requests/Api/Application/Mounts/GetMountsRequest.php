<?php

namespace DarkOak\Http\Requests\Api\Application\Mounts;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class GetMountsRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::MOUNTS_READ;
    }
}


<?php

namespace DarkOak\Http\Requests\Api\Application\Api;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class GetApplicationApiKeysRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::API_READ;
    }
}


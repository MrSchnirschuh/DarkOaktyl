<?php

namespace DarkOak\Http\Requests\Api\Application\Servers;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class GetServerActivityRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::ACTIVITY_READ;
    }
}

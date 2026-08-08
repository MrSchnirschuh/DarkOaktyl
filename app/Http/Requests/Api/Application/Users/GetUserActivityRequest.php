<?php

namespace DarkOak\Http\Requests\Api\Application\Users;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class GetUserActivityRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::ACTIVITY_READ;
    }
}

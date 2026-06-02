<?php

namespace DarkOak\Http\Requests\Api\Application;

use DarkOak\Models\AdminRole;

class ActivityRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::ACTIVITY_READ;
    }
}

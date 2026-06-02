<?php

namespace DarkOak\Http\Requests\Api\Application;

use DarkOak\Models\AdminRole;

class OverviewRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::OVERVIEW_READ;
    }
}

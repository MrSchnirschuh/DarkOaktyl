<?php

namespace DarkOak\Http\Requests\Api\Application\Theme;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateThemeRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::SETTINGS_UPDATE;
    }
}

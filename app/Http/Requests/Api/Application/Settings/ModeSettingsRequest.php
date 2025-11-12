<?php

namespace DarkOak\Http\Requests\Api\Application\Settings;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class ModeSettingsRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'mode' => 'nullable|string|in:standard,personal',
        ];
    }

    public function permission(): string
    {
        return AdminRole::SETTINGS_UPDATE;
    }
}


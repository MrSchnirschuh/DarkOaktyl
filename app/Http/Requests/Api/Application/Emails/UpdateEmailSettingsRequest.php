<?php

namespace DarkOak\Http\Requests\Api\Application\Emails;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateEmailSettingsRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::EMAILS_UPDATE;
    }

    public function rules(): array
    {
        return [
            'key' => 'required|string|in:enabled,default_theme',
            'value' => 'nullable',
        ];
    }
}


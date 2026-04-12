<?php

namespace DarkOak\Http\Requests\Api\Application\AI;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class AISettingsRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'enabled' => 'nullable|bool',
            'key' => 'nullable',
            'user_access' => 'nullable|bool',
        ];
    }

    public function permission(): string
    {
        return AdminRole::AI_UPDATE;
    }
}


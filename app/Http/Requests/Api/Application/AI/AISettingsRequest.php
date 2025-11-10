<?php

namespace Everest\Http\Requests\Api\Application\AI;

use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

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

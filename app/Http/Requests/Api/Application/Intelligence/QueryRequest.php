<?php

namespace DarkOak\Http\Requests\Api\Application\AI;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class AIQueryRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'query' => 'required|string|min:3',
        ];
    }

    public function permission(): string
    {
        return AdminRole::AI_READ;
    }
}


<?php

namespace DarkOak\Http\Requests\Api\Application\Emails;

use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;
use DarkOak\Models\AdminRole;

class UpdateEmailEnvironmentRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::EMAILS_UPDATE;
    }

    public function rules(): array
    {
        return [
            'mailer' => 'required|string',
            'host' => 'sometimes|nullable|string',
            'port' => 'sometimes|nullable|string',
            'username' => 'sometimes|nullable|string',
            'password' => 'sometimes|nullable|string',
            'encryption' => 'sometimes|nullable|string',
            'from_address' => 'sometimes|nullable|email',
            'from_name' => 'sometimes|nullable|string',
        ];
    }
}

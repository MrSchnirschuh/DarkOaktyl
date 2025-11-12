<?php

namespace Everest\Http\Requests\Api\Application\Emails;

use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class SendTestEmailTemplateRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::EMAILS_UPDATE;
    }

    public function rules(): array
    {
        return [
            'email' => 'required_without:user_id|nullable|email',
            'user_id' => 'required_without:email|nullable|integer|exists:users,id',
            'data' => 'sometimes|array',
        ];
    }
}

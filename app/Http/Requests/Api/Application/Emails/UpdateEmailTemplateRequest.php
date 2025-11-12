<?php

namespace DarkOak\Http\Requests\Api\Application\Emails;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateEmailTemplateRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::EMAILS_UPDATE;
    }

    public function rules(): array
    {
        return [
            'key' => 'sometimes|string|max:191',
            'name' => 'sometimes|string|max:191',
            'subject' => 'sometimes|string|max:191',
            'description' => 'sometimes|nullable|string',
            'content' => 'sometimes|string',
            'locale' => 'sometimes|string|max:12',
            'is_enabled' => 'sometimes|boolean',
            'theme_uuid' => 'sometimes|nullable|string|exists:email_themes,uuid',
            'metadata' => 'sometimes|array',
        ];
    }
}


<?php

namespace DarkOak\Http\Requests\Api\Application\Emails;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class PreviewEmailTemplateRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::EMAILS_UPDATE;
    }

    public function rules(): array
    {
        return [
            'subject' => 'required|string',
            'content' => 'required|string',
            'theme_uuid' => 'nullable|string|exists:email_themes,uuid',
            'data' => 'sometimes|array',
            'metadata' => 'sometimes|array',
        ];
    }
}


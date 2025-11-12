<?php

namespace DarkOak\Http\Requests\Api\Application\Emails;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreEmailTemplateRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::EMAILS_UPDATE;
    }

    public function rules(): array
    {
        return [
            'key' => 'required|string|max:191|unique:email_templates,key',
            'name' => 'required|string|max:191',
            'subject' => 'required|string|max:191',
            'description' => 'nullable|string',
            'content' => 'required|string',
            'locale' => 'required|string|max:12',
            'is_enabled' => 'sometimes|boolean',
            'theme_uuid' => 'nullable|string|exists:email_themes,uuid',
            'metadata' => 'sometimes|array',
        ];
    }
}


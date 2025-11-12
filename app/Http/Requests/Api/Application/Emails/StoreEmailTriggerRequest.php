<?php

namespace DarkOak\Http\Requests\Api\Application\Emails;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreEmailTriggerRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::EMAILS_TRIGGERS;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:191',
            'description' => 'nullable|string',
            'trigger_type' => 'required|string|in:event,schedule,resource',
            'schedule_type' => 'nullable|string|in:once,recurring',
            'event_key' => 'required_if:trigger_type,event|string|max:191',
            'schedule_at' => 'nullable|date',
            'cron_expression' => 'required_if:schedule_type,recurring|string|max:191',
            'timezone' => 'nullable|timezone',
            'template_uuid' => 'required|string|exists:email_templates,uuid',
            'payload' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
        ];
    }
}


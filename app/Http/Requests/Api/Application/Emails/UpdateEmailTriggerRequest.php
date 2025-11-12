<?php

namespace Everest\Http\Requests\Api\Application\Emails;

class UpdateEmailTriggerRequest extends StoreEmailTriggerRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:191',
            'description' => 'sometimes|nullable|string',
            'trigger_type' => 'sometimes|string|in:event,schedule,resource',
            'schedule_type' => 'sometimes|string|in:once,recurring',
            'event_key' => 'sometimes|nullable|string|max:191',
            'schedule_at' => 'sometimes|nullable|date',
            'cron_expression' => 'sometimes|nullable|string|max:191',
            'timezone' => 'sometimes|timezone',
            'template_uuid' => 'sometimes|string|exists:email_templates,uuid',
            'payload' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

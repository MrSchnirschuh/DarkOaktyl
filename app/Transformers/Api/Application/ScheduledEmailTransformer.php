<?php

namespace Everest\Transformers\Api\Application;

use Everest\Models\ScheduledEmail;
use Everest\Transformers\Api\Transformer;

class ScheduledEmailTransformer extends Transformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return 'scheduled_email';
    }

    /**
     * Transform scheduled email into a representation for the application API.
     */
    public function transform(ScheduledEmail $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'template_key' => $model->template_key,
            'template' => $model->template ? [
                'key' => $model->template->key,
                'name' => $model->template->name,
            ] : null,
            'trigger_type' => $model->trigger_type,
            'trigger_value' => $model->trigger_value,
            'event_name' => $model->event_name,
            'recipients' => $model->recipients ?? [],
            'template_data' => $model->template_data ?? [],
            'enabled' => $model->enabled,
            'last_run_at' => $model->last_run_at?->toIso8601String(),
            'next_run_at' => $model->next_run_at?->toIso8601String(),
            'created_at' => $model->created_at?->toIso8601String(),
            'updated_at' => $model->updated_at?->toIso8601String(),
        ];
    }
}

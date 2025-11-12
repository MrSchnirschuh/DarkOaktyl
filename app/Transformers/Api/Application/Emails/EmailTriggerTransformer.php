<?php

namespace Everest\Transformers\Api\Application\Emails;

use Everest\Models\EmailTrigger;
use Everest\Transformers\Api\Transformer;

class EmailTriggerTransformer extends Transformer
{
    protected array $availableIncludes = ['template'];

    public function getResourceName(): string
    {
        return 'email_trigger';
    }

    public function transform(EmailTrigger $trigger): array
    {
        return [
            'id' => $trigger->id,
            'uuid' => $trigger->uuid,
            'name' => $trigger->name,
            'description' => $trigger->description,
            'trigger_type' => $trigger->trigger_type,
            'schedule_type' => $trigger->schedule_type,
            'event_key' => $trigger->event_key,
            'schedule_at' => optional($trigger->schedule_at)->toAtomString(),
            'cron_expression' => $trigger->cron_expression,
            'timezone' => $trigger->timezone,
            'template_uuid' => optional($trigger->template)->uuid,
            'payload' => $trigger->payload ?? [],
            'is_active' => $trigger->is_active,
            'last_run_at' => optional($trigger->last_run_at)->toAtomString(),
            'next_run_at' => optional($trigger->next_run_at)->toAtomString(),
            'created_at' => optional($trigger->created_at)->toAtomString(),
            'updated_at' => optional($trigger->updated_at)->toAtomString(),
        ];
    }

    public function includeTemplate(EmailTrigger $trigger)
    {
        $template = $trigger->template;

        return $template ? $this->item($template, new EmailTemplateTransformer(), 'email_template') : null;
    }
}

<?php

namespace Everest\Transformers\Api\Application;

use Everest\Models\EmailTemplate;
use Everest\Transformers\Api\Transformer;

class EmailTemplateTransformer extends Transformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return 'email_template';
    }

    /**
     * Transform email template into a representation for the application API.
     */
    public function transform(EmailTemplate $model): array
    {
        return [
            'id' => $model->id,
            'key' => $model->key,
            'name' => $model->name,
            'subject' => $model->subject,
            'body_html' => $model->body_html,
            'body_text' => $model->body_text,
            'variables' => $model->variables ?? [],
            'enabled' => $model->enabled,
            'created_at' => $model->created_at?->toIso8601String(),
            'updated_at' => $model->updated_at?->toIso8601String(),
        ];
    }
}

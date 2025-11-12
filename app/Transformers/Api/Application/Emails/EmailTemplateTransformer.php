<?php

namespace DarkOak\Transformers\Api\Application\Emails;

use DarkOak\Models\EmailTemplate;
use DarkOak\Transformers\Api\Transformer;

class EmailTemplateTransformer extends Transformer
{
    protected array $availableIncludes = ['theme'];

    public function getResourceName(): string
    {
        return 'email_template';
    }

    public function transform(EmailTemplate $template): array
    {
        return [
            'id' => $template->id,
            'uuid' => $template->uuid,
            'key' => $template->key,
            'name' => $template->name,
            'description' => $template->description,
            'subject' => $template->subject,
            'content' => $template->content,
            'locale' => $template->locale,
            'is_enabled' => $template->is_enabled,
            'theme_uuid' => optional($template->theme)->uuid,
            'metadata' => $template->metadata ?? [],
            'created_at' => optional($template->created_at)->toAtomString(),
            'updated_at' => optional($template->updated_at)->toAtomString(),
        ];
    }

    public function includeTheme(EmailTemplate $template)
    {
        $theme = $template->theme;

        return $theme ? $this->item($theme, new EmailThemeTransformer(), 'email_theme') : null;
    }
}


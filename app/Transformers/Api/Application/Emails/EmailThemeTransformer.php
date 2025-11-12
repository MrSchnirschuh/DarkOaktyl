<?php

namespace Everest\Transformers\Api\Application\Emails;

use Everest\Models\EmailTheme;
use Everest\Transformers\Api\Transformer;

class EmailThemeTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return 'email_theme';
    }

    public function transform(EmailTheme $theme): array
    {
        return [
            'id' => $theme->id,
            'uuid' => $theme->uuid,
            'name' => $theme->name,
            'description' => $theme->description,
            'colors' => [
                'primary' => $theme->primary_color,
                'secondary' => $theme->secondary_color,
                'accent' => $theme->accent_color,
                'background' => $theme->background_color,
                'body' => $theme->body_color,
                'text' => $theme->text_color,
                'muted' => $theme->muted_text_color,
                'button' => $theme->button_color,
                'button_text' => $theme->button_text_color,
            ],
            'logo_url' => $theme->logo_url,
            'footer_text' => $theme->footer_text,
            'is_default' => $theme->is_default,
            'variant_mode' => $theme->variant_mode ?? 'single',
            'light_colors' => $theme->light_palette ? [
                'primary' => $theme->light_palette['primary'] ?? null,
                'secondary' => $theme->light_palette['secondary'] ?? null,
                'accent' => $theme->light_palette['accent'] ?? null,
                'background' => $theme->light_palette['background'] ?? null,
                'body' => $theme->light_palette['body'] ?? null,
                'text' => $theme->light_palette['text'] ?? null,
                'muted' => $theme->light_palette['muted'] ?? null,
                'button' => $theme->light_palette['button'] ?? null,
                'button_text' => $theme->light_palette['button_text'] ?? null,
            ] : null,
            'meta' => $theme->meta ?? [],
            'created_at' => optional($theme->created_at)->toAtomString(),
            'updated_at' => optional($theme->updated_at)->toAtomString(),
        ];
    }
}

<?php

namespace Everest\Transformers\Api\Application\Emails;

use Everest\Models\EmailTheme;
use Everest\Services\Themes\ThemePaletteService;
use Everest\Transformers\Api\Transformer;

class EmailThemeTransformer extends Transformer
{
    private ThemePaletteService $paletteService;

    public function handle(ThemePaletteService $paletteService): void
    {
        $this->paletteService = $paletteService;
    }

    public function getResourceName(): string
    {
        return 'email_theme';
    }

    public function transform(EmailTheme $theme): array
    {
        $canonicalPalettes = null;

        if ($theme->is_default ?? false) {
            $canonicalPalettes = $this->paletteService->getEmailPalettes();
        }

        $darkPalette = $canonicalPalettes['dark'] ?? [
            'primary_color' => $theme->primary_color,
            'secondary_color' => $theme->secondary_color,
            'accent_color' => $theme->accent_color,
            'background_color' => $theme->background_color,
            'body_color' => $theme->body_color,
            'text_color' => $theme->text_color,
            'muted_text_color' => $theme->muted_text_color,
            'button_color' => $theme->button_color,
            'button_text_color' => $theme->button_text_color,
        ];

        $lightPalette = $canonicalPalettes['light'] ?? ($theme->light_palette ?? null);

        $variantMode = $canonicalPalettes ? 'dual' : ($theme->variant_mode ?? 'single');

        return [
            'id' => $theme->id,
            'uuid' => $theme->uuid,
            'name' => $theme->name,
            'description' => $theme->description,
            'colors' => [
                'primary' => $darkPalette['primary_color'] ?? null,
                'secondary' => $darkPalette['secondary_color'] ?? null,
                'accent' => $darkPalette['accent_color'] ?? null,
                'background' => $darkPalette['background_color'] ?? null,
                'body' => $darkPalette['body_color'] ?? null,
                'text' => $darkPalette['text_color'] ?? null,
                'muted' => $darkPalette['muted_text_color'] ?? null,
                'button' => $darkPalette['button_color'] ?? null,
                'button_text' => $darkPalette['button_text_color'] ?? null,
            ],
            'logo_url' => $theme->logo_url,
            'footer_text' => $theme->footer_text,
            'is_default' => $theme->is_default,
            'variant_mode' => $variantMode,
            'light_colors' => $lightPalette ? [
                'primary' => $lightPalette['primary_color'] ?? $lightPalette['primary'] ?? null,
                'secondary' => $lightPalette['secondary_color'] ?? $lightPalette['secondary'] ?? null,
                'accent' => $lightPalette['accent_color'] ?? $lightPalette['accent'] ?? null,
                'background' => $lightPalette['background_color'] ?? $lightPalette['background'] ?? null,
                'body' => $lightPalette['body_color'] ?? $lightPalette['body'] ?? null,
                'text' => $lightPalette['text_color'] ?? $lightPalette['text'] ?? null,
                'muted' => $lightPalette['muted_text_color'] ?? $lightPalette['muted'] ?? null,
                'button' => $lightPalette['button_color'] ?? $lightPalette['button'] ?? null,
                'button_text' => $lightPalette['button_text_color'] ?? $lightPalette['button_text'] ?? null,
            ] : null,
            'meta' => $theme->meta ?? [],
            'created_at' => optional($theme->created_at)->toAtomString(),
            'updated_at' => optional($theme->updated_at)->toAtomString(),
        ];
    }
}

<?php

namespace Everest\Services\Emails;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Blade;
use Everest\Models\EmailTheme;
use Everest\Models\EmailTemplate;
use Everest\Models\User;
use Everest\Services\Themes\ThemePaletteService;

class EmailTemplateRenderer
{
    public function __construct(private ThemePaletteService $paletteService)
    {
    }

    public function render(EmailTemplate $template, array $data = []): array
    {
        $template->loadMissing('theme');

        $theme = $template->theme ?: $this->resolveDefaultTheme();

        if ($theme->is_default) {
            $theme = $this->syncDefaultThemeWithPanel($theme);
        }

        $resolvedMode = $this->determineAppearanceMode($data['user'] ?? null, $data);

        $variants = [
            'dark' => $this->applyPaletteForMode($theme, 'dark'),
            'light' => $this->applyPaletteForMode($theme, 'light'),
        ];

        $themeForMode = clone ($variants[$resolvedMode] ?? $variants['dark']);

        $paletteContext = [
            'dark' => $this->formatPaletteForView($variants['dark']),
            'light' => $this->formatPaletteForView($variants['light']),
        ];

        $viewData = array_merge($data, [
            'theme' => $themeForMode,
            'template' => $template,
            'themeMode' => $resolvedMode,
            'palettes' => $paletteContext,
        ]);

        $subject = trim(preg_replace('/\s+/', ' ', Blade::render($template->subject, $viewData)));

        $rendered = Blade::render($template->content, $viewData);
        $htmlBody = Str::markdown($rendered, [
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        $html = view('emails.layout', [
            'theme' => $themeForMode,
            'content' => $htmlBody,
            'metadata' => $template->metadata ?? [],
            'themeMode' => $resolvedMode,
            'palettes' => $paletteContext,
        ])->render();

        $text = trim(strip_tags($htmlBody));

        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
            'theme' => $themeForMode,
        ];
    }

    protected function resolveDefaultTheme(): EmailTheme
    {
        $defaults = config('modules.email.defaults', []);

        $theme = EmailTheme::query()->where('is_default', true)->first();
        if ($theme) {
            return $theme;
        }

        $variant = Arr::get($defaults, 'variant_mode', 'single');
        $lightPalette = Arr::get($defaults, 'light_palette');

        return new EmailTheme([
            'name' => Arr::get($defaults, 'name', 'DarkOaktyl Default'),
            'primary_color' => Arr::get($defaults, 'primary_color', '#2563eb'),
            'secondary_color' => Arr::get($defaults, 'secondary_color', '#1e40af'),
            'accent_color' => Arr::get($defaults, 'accent_color', '#f97316'),
            'background_color' => Arr::get($defaults, 'background_color', '#0f172a'),
            'body_color' => Arr::get($defaults, 'body_color', '#ffffff'),
            'text_color' => Arr::get($defaults, 'text_color', '#0f172a'),
            'muted_text_color' => Arr::get($defaults, 'muted_text_color', '#475569'),
            'button_color' => Arr::get($defaults, 'button_color', '#2563eb'),
            'button_text_color' => Arr::get($defaults, 'button_text_color', '#ffffff'),
            'footer_text' => Arr::get($defaults, 'footer_text', ''),
            'is_default' => true,
            'variant_mode' => $variant,
            'light_palette' => is_array($lightPalette) ? $lightPalette : null,
        ]);
    }

    private function determineAppearanceMode(?User $user, array $data): string
    {
        $explicit = Arr::get($data, 'theme_mode');
        if (in_array($explicit, ['light', 'dark'], true)) {
            return $explicit;
        }

        $appearance = Arr::get($data, 'appearance');
        if (is_array($appearance)) {
            $preferred = $this->resolvePreference($appearance['mode'] ?? null, $appearance['last_mode'] ?? ($appearance['lastMode'] ?? null));
            if ($preferred) {
                return $preferred;
            }
        }

        if ($user) {
            $preferred = $this->resolvePreference($user->appearance_mode ?? null, $user->appearance_last_mode ?? null);
            if ($preferred) {
                return $preferred;
            }
        }

        return 'dark';
    }

    private function resolvePreference(?string $mode, ?string $lastMode): ?string
    {
        if (in_array($mode, ['light', 'dark'], true)) {
            return $mode;
        }

        if ($mode === 'system') {
            if (in_array($lastMode, ['light', 'dark'], true)) {
                return $lastMode;
            }

            return 'dark';
        }

        return null;
    }

    private function applyPaletteForMode(EmailTheme $theme, string $mode): EmailTheme
    {
        $mode = $mode === 'light' ? 'light' : 'dark';

        if ($mode === 'light' && $theme->variant_mode === 'dual') {
            $palette = $theme->light_palette ?? [];
            if (is_array($palette) && !empty($palette)) {
                $clone = clone $theme;

                $clone->primary_color = $palette['primary'] ?? $clone->primary_color;
                $clone->secondary_color = $palette['secondary'] ?? $clone->secondary_color;
                $clone->accent_color = $palette['accent'] ?? $clone->accent_color;
                $clone->background_color = $palette['background'] ?? $clone->background_color;
                $clone->body_color = $palette['body'] ?? $clone->body_color;
                $clone->text_color = $palette['text'] ?? $clone->text_color;
                $clone->muted_text_color = $palette['muted'] ?? $clone->muted_text_color;
                $clone->button_color = $palette['button'] ?? $clone->button_color;
                $clone->button_text_color = $palette['button_text'] ?? $clone->button_text_color;

                return $clone;
            }
        }

        return clone $theme;
    }

    private function syncDefaultThemeWithPanel(EmailTheme $theme): EmailTheme
    {
        $palettes = $this->paletteService->getEmailPalettes();

        if (empty($palettes['dark']) || empty($palettes['light'])) {
            return clone $theme;
        }

        $clone = clone $theme;

        foreach ($palettes['dark'] as $attribute => $value) {
            $clone->{$attribute} = $value;
        }

        $clone->variant_mode = 'dual';
        $clone->light_palette = [
            'primary' => $palettes['light']['primary_color'],
            'secondary' => $palettes['light']['secondary_color'],
            'accent' => $palettes['light']['accent_color'],
            'background' => $palettes['light']['background_color'],
            'body' => $palettes['light']['body_color'],
            'text' => $palettes['light']['text_color'],
            'muted' => $palettes['light']['muted_text_color'],
            'button' => $palettes['light']['button_color'],
            'button_text' => $palettes['light']['button_text_color'],
        ];

        return $clone;
    }

    private function formatPaletteForView(EmailTheme $theme): array
    {
        return [
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
    }
}

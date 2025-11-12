<?php

namespace Everest\Services\Themes;

use Everest\Contracts\Repository\ThemeRepositoryInterface;
use Everest\Models\EmailTheme;
use Illuminate\Support\Facades\Log;

class ThemePaletteService
{
    public function __construct(private ThemeRepositoryInterface $repository)
    {
    }

    /**
     * Returns the merged theme color configuration combining config defaults and stored overrides.
     */
    public function getRawColors(): array
    {
        $colors = config('modules.theme.colors', []);

        try {
            foreach ($this->repository->all() as $setting) {
                if (!isset($setting->key) || !str_starts_with($setting->key, 'theme::colors:')) {
                    continue;
                }

                $name = substr($setting->key, strlen('theme::colors:'));
                $colors[$name] = $setting->value;
            }
        } catch (\Throwable $exception) {
            // Repository access can fail during migrations or early bootstrap — fall back to config defaults.
        }

        return $colors;
    }

    /**
     * Builds a canonical light and dark palette derived from the configured theme colors.
     */
    public function getPalettes(): array
    {
        $canonical = $this->getCanonicalPalettes();

        return [
            'dark' => $canonical['dark']['tokens'],
            'light' => $canonical['light']['tokens'],
        ];
    }

    /**
     * Builds palettes formatted for email usage (hex encoded with expected attribute names).
     */
    public function getEmailPalettes(): array
    {
        $canonical = $this->getCanonicalPalettes();

        return [
            'dark' => $canonical['dark']['email'],
            'light' => $canonical['light']['email'],
        ];
    }

    /**
     * Returns the email defaults array with colors synchronized to the canonical panel palette.
     */
    public function getEmailDefaults(): array
    {
        $defaults = config('modules.email.defaults', []);
        $palettes = $this->getEmailPalettes();

        $dark = $palettes['dark'];
        $light = $palettes['light'];

        return array_merge($defaults, [
            'primary_color' => $dark['primary_color'],
            'secondary_color' => $dark['secondary_color'],
            'accent_color' => $dark['accent_color'],
            'background_color' => $dark['background_color'],
            'body_color' => $dark['body_color'],
            'text_color' => $dark['text_color'],
            'muted_text_color' => $dark['muted_text_color'],
            'button_color' => $dark['button_color'],
            'button_text_color' => $dark['button_text_color'],
            'variant_mode' => 'dual',
            'light_palette' => [
                'primary' => $light['primary_color'],
                'secondary' => $light['secondary_color'],
                'accent' => $light['accent_color'],
                'background' => $light['background_color'],
                'body' => $light['body_color'],
                'text' => $light['text_color'],
                'muted' => $light['muted_text_color'],
                'button' => $light['button_color'],
                'button_text' => $light['button_text_color'],
            ],
        ]);
    }

    /**
     * Returns the fully normalized palette metadata shared across panel and mail rendering.
     */
    public function getCanonicalPalettes(): array
    {
        $colors = $this->getRawColors();

        $dark = $this->buildCanonicalPalette($colors, 'dark');
        $light = $this->buildCanonicalPalette($colors, 'light', $dark['tokens']);

        return [
            'dark' => $dark,
            'light' => $light,
        ];
    }

    private function buildCanonicalPalette(array $colors, string $mode, array $referenceTokens = []): array
    {
        $suffix = $mode === 'light' ? '_light' : '_dark';
        $alternateSuffix = $mode === 'light' ? '_dark' : '_light';
        $textModeKey = 'text_' . ($mode === 'light' ? 'light' : 'dark');

        $fallbacks = [
            'primary' => '#008000',
            'secondary_dark' => '#18181B',
            'secondary_light' => '#E2E8F0',
            'background_dark' => '#141414',
            'background_light' => '#F4F4F5',
            'headers_dark' => '#171717',
            'headers_light' => '#E5E7EB',
            'sidebar_dark' => '#18181B',
            'sidebar_light' => '#E4E4E7',
            'body_dark' => '#171717',
            'body_light' => '#FAFAFA',
            'text_primary_dark' => '#F5F5F5',
            'text_primary_light' => '#0F172A',
            'text_secondary_dark' => '#D4D4D8',
            'text_secondary_light' => '#334155',
            'muted_dark' => '#A1A1AA',
            'muted_light' => '#475569',
            'accent_primary_light' => '#008000',
            'accent_primary_dark' => '#008000',
            'accent_primary' => '#008000',
            'accent_secondary' => '#1F2937',
            'button' => '#008000',
        ];

        $secondaryFallbackKey = $mode === 'light' ? 'secondary_light' : 'secondary_dark';
        $backgroundFallbackKey = $mode === 'light' ? 'background_light' : 'background_dark';
        $headersFallbackKey = $mode === 'light' ? 'headers_light' : 'headers_dark';
        $sidebarFallbackKey = $mode === 'light' ? 'sidebar_light' : 'sidebar_dark';
        $bodyFallbackKey = $mode === 'light' ? 'body_light' : 'body_dark';
        $textPrimaryFallbackKey = $mode === 'light' ? 'text_primary_light' : 'text_primary_dark';
        $textSecondaryFallbackKey = $mode === 'light' ? 'text_secondary_light' : 'text_secondary_dark';
        $mutedFallbackKey = $mode === 'light' ? 'muted_light' : 'muted_dark';

        $primary = $this->colorToHex($this->resolveColor($colors, ["primary{$suffix}", 'primary']), $fallbacks['primary']);

        $secondary = $this->colorToHex($this->resolveColor($colors, ["secondary{$suffix}", 'secondary', "secondary{$alternateSuffix}"]), $fallbacks[$secondaryFallbackKey]);

        $background = $this->colorToHex($this->resolveColor($colors, ["background{$suffix}", 'background', "background{$alternateSuffix}"]), $fallbacks[$backgroundFallbackKey]);

        $headers = $this->colorToHex($this->resolveColor($colors, ["headers{$suffix}", 'headers', "headers{$alternateSuffix}"]), $fallbacks[$headersFallbackKey]);

        $body = $this->colorToHex($this->resolveColor($colors, ["body{$suffix}", "headers{$suffix}", 'body', 'headers']), $fallbacks[$bodyFallbackKey]);

        $sidebar = $this->colorToHex($this->resolveColor($colors, ["sidebar{$suffix}", 'sidebar', "sidebar{$alternateSuffix}"]), $fallbacks[$sidebarFallbackKey]);

        $accentPrimaryFallbackKey = $mode === 'light' ? 'accent_primary_light' : 'accent_primary_dark';
        $accentPrimary = $this->colorToHex(
            $this->resolveColor($colors, ["accent_primary{$suffix}", 'accent_primary']),
            $fallbacks[$accentPrimaryFallbackKey] ?? $fallbacks['accent_primary']
        );

        $accentSecondary = $this->colorToHex($this->resolveColor($colors, ["accent_secondary{$suffix}", 'accent_secondary', "accent_secondary{$alternateSuffix}"]), $fallbacks['accent_secondary']);

        $textPrimaryCandidate = $this->resolveColor($colors, ["text_primary{$suffix}", $textModeKey, 'text_primary', 'text']);
        $textPrimary = $this->ensureContrast($textPrimaryCandidate, $background, $fallbacks[$textPrimaryFallbackKey], 4.5);

        $textSecondaryCandidate = $this->resolveColor($colors, ["text_secondary{$suffix}", 'text_secondary', $textModeKey, 'text_secondary', 'text']);
        $textSecondary = $this->ensureContrast($textSecondaryCandidate, $background, $fallbacks[$textSecondaryFallbackKey], 3.5);

        $mutedCandidate = $this->resolveColor($colors, ["muted_text{$suffix}", "text_secondary{$suffix}", 'muted_text', 'text_secondary']);
        $mutedText = $this->ensureContrast($mutedCandidate, $background, $fallbacks[$mutedFallbackKey], 3.0);

        $button = $this->colorToHex($this->resolveColor($colors, ["button{$suffix}", 'button', "accent_primary{$suffix}", 'accent_primary']), $fallbacks['button']);

        $buttonTextFallback = $mode === 'light' ? '#0F0F0F' : '#F5F5F5';
        $buttonTextCandidate = $this->resolveColor($colors, ["button_text{$suffix}", 'button_text', $textModeKey, "text_primary{$suffix}", 'text_primary']);
        $buttonText = $this->ensureContrast($buttonTextCandidate, $button, $buttonTextFallback, 4.5);

        $textInverseFallback = $mode === 'light' ? '#F5F5F5' : '#09090B';
        $textInverseCandidate = $this->resolveColor($colors, ["text_inverse{$suffix}", 'text_inverse']);
        $textInverse = $this->ensureContrast($textInverseCandidate, $textPrimary, $textInverseFallback, 4.5);

        $tokens = [
            'primary' => $primary,
            'secondary' => $secondary,
            'background' => $background,
            'headers' => $headers,
            'body' => $body,
            'sidebar' => $sidebar,
            'accent_primary' => $accentPrimary,
            'accent_secondary' => $accentSecondary,
            'text_primary' => $textPrimary,
            'text_secondary' => $textSecondary,
            'muted_text' => $mutedText,
            'text_inverse' => $textInverse,
            'button' => $button,
            'button_text' => $buttonText,
        ];

        $textPalette = [
            'primary' => $textPrimary,
            'secondary' => $textSecondary,
            'muted' => $mutedText,
            'inverse' => $textInverse,
            'on_accent' => $buttonText,
        ];

        $surfacePalette = [
            'background' => $background,
            'body' => $body,
            'headers' => $headers,
            'sidebar' => $sidebar,
            'card' => $body,
        ];

        $emailPalette = [
            'primary_color' => $accentPrimary,
            'secondary_color' => $secondary,
            'accent_color' => $accentSecondary,
            'background_color' => $background,
            'body_color' => $body,
            'text_color' => $textPrimary,
            'muted_text_color' => $mutedText,
            'button_color' => $button,
            'button_text_color' => $buttonText,
        ];

        return [
            'tokens' => $tokens,
            'text' => $textPalette,
            'surfaces' => $surfacePalette,
            'email' => $emailPalette,
        ];
    }

    private function resolveColor(array $colors, array $candidates, ?string $fallback = null): string
    {
        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || $candidate === '') {
                continue;
            }

            if (array_key_exists($candidate, $colors) && is_string($colors[$candidate]) && trim($colors[$candidate]) !== '') {
                return $colors[$candidate];
            }
        }

        return $fallback ?? '#000000';
    }

    private function colorToHex(?string $value, string $fallback): string
    {
        if (!is_string($value) || trim($value) === '') {
            return $this->colorToHex($fallback, '#000000');
        }

        $value = trim($value);

        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value, $matches)) {
            $hex = $matches[1];
            if (strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }

            if (strlen($hex) === 8) {
                $hex = substr($hex, 0, 6);
            }

            return '#' . strtoupper($hex);
        }

        if (preg_match('/^rgba?\(([^)]+)\)$/i', $value, $matches)) {
            $components = preg_split('/[,\s\/]+/', trim($matches[1]));
            $components = array_values(array_filter($components, 'strlen'));

            if (count($components) >= 3) {
                [$r, $g, $b] = array_map([$this, 'normalizeRgbComponent'], array_slice($components, 0, 3));

                return sprintf('#%02X%02X%02X', $r, $g, $b);
            }
        }

        if (preg_match('/^hsla?\(([^)]+)\)$/i', $value, $matches)) {
            $components = preg_split('/[,\s\/]+/', trim($matches[1]));
            $components = array_values(array_filter($components, 'strlen'));

            if (count($components) >= 3) {
                $h = fmod((float) $components[0], 360.0);
                if ($h < 0) {
                    $h += 360.0;
                }
                $s = $this->normalizePercentage($components[1]);
                $l = $this->normalizePercentage($components[2]);

                [$r, $g, $b] = $this->hslToRgb($h / 360.0, $s, $l);

                return sprintf('#%02X%02X%02X', $r, $g, $b);
            }
        }

        return $this->colorToHex($fallback, '#000000');
    }

    private function ensureContrast(?string $foreground, string $background, string $fallback, float $minRatio): string
    {
        $backgroundHex = $this->colorToHex($background, '#000000');
        $candidate = $this->colorToHex($foreground, $fallback);
        if ($this->contrastRatio($candidate, $backgroundHex) >= $minRatio) {
            return $candidate;
        }

        $fallbackHex = $this->colorToHex($fallback, $fallback);
        if ($this->contrastRatio($fallbackHex, $backgroundHex) >= $minRatio) {
            return $fallbackHex;
        }

        $darkContrast = $this->contrastRatio('#000000', $backgroundHex);
        $lightContrast = $this->contrastRatio('#FFFFFF', $backgroundHex);

        return $darkContrast >= $lightContrast ? '#000000' : '#FFFFFF';
    }

    private function contrastRatio(string $foreground, string $background): float
    {
        $fg = $this->relativeLuminance($foreground);
        $bg = $this->relativeLuminance($background);

        $brighter = max($fg, $bg);
        $darker = min($fg, $bg);

        return ($brighter + 0.05) / ($darker + 0.05);
    }

    private function relativeLuminance(string $color): float
    {
        $color = strtoupper(trim($color));
        if (!preg_match('/^#([0-9A-F]{6})$/', $color, $matches)) {
            $color = strtoupper($this->colorToHex($color, '#000000'));
            if (!preg_match('/^#([0-9A-F]{6})$/', $color, $matches)) {
                return 0.0;
            }
        }

        $hex = $matches[1];
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $transform = static function (int $channel): float {
            $c = $channel / 255;
            return $c <= 0.03928 ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4);
        };

        $rLinear = $transform($r);
        $gLinear = $transform($g);
        $bLinear = $transform($b);

        return 0.2126 * $rLinear + 0.7152 * $gLinear + 0.0722 * $bLinear;
    }

    private function normalizeRgbComponent(string $component): int
    {
        $value = (float) $component;

        if ($value <= 1.0) {
            $value *= 255.0;
        }

        return max(0, min(255, (int) round($value)));
    }

    private function normalizePercentage(string $value): float
    {
        $value = trim($value);
        if (str_ends_with($value, '%')) {
            $value = substr($value, 0, -1);
            return max(0.0, min(1.0, ((float) $value) / 100.0));
        }

        $float = (float) $value;
        if ($float > 1.0) {
            $float /= 100.0;
        }

        return max(0.0, min(1.0, $float));
    }

    private function hslToRgb(float $h, float $s, float $l): array
    {
        if ($s == 0.0) {
            $v = (int) round($l * 255.0);
            return [$v, $v, $v];
        }

        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;

        $r = $this->hueToRgb($p, $q, $h + 1 / 3);
        $g = $this->hueToRgb($p, $q, $h);
        $b = $this->hueToRgb($p, $q, $h - 1 / 3);

        return [
            (int) round($r * 255.0),
            (int) round($g * 255.0),
            (int) round($b * 255.0),
        ];
    }

    private function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }

    public function syncDefaultEmailTheme(): void
    {
        try {
            $defaultTheme = EmailTheme::query()->where('is_default', true)->first();
            if (!$defaultTheme) {
                return;
            }

            $defaults = $this->getEmailDefaults();

            $expectedLightPalette = $defaults['light_palette'] ?? null;
            $changes = [];

            $fields = [
                'primary_color',
                'secondary_color',
                'accent_color',
                'background_color',
                'body_color',
                'text_color',
                'muted_text_color',
                'button_color',
                'button_text_color',
            ];

            foreach ($fields as $field) {
                if (!array_key_exists($field, $defaults)) {
                    continue;
                }

                $expected = $defaults[$field];
                if ($defaultTheme->{$field} !== $expected) {
                    $changes[$field] = $expected;
                }
            }

            $variantMode = $defaults['variant_mode'] ?? 'dual';
            if ($defaultTheme->variant_mode !== $variantMode) {
                $changes['variant_mode'] = $variantMode;
            }

            if ($expectedLightPalette !== null) {
                if ($defaultTheme->light_palette !== $expectedLightPalette) {
                    $changes['light_palette'] = $expectedLightPalette;
                }
            } elseif ($defaultTheme->light_palette !== null) {
                $changes['light_palette'] = null;
            }

            if (empty($changes)) {
                return;
            }

            $defaultTheme->forceFill($changes)->save();
        } catch (\Throwable $exception) {
            Log::warning('Failed to synchronize default email theme with panel palette.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

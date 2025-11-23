@php
    use Illuminate\Support\Facades\Auth;

    $user = Auth::user();
    $appearanceMode = $user?->appearance_mode ?? 'system';
    $appearanceLastMode = $user?->appearance_last_mode ?? 'dark';
    $initialMode = match ($appearanceMode) {
        'light' => 'light',
        'dark' => 'dark',
        default => $appearanceLastMode === 'light' ? 'light' : 'dark',
    };

    $palettes = $themeConfiguration['palettes'] ?? [];
    $surfacePalettes = $themeConfiguration['surfacePalettes'] ?? [];
    $textPalettes = $themeConfiguration['textPalettes'] ?? [];

    $darkPalette = $palettes['dark'] ?? [];
    $lightPalette = $palettes['light'] ?? [];
    $darkSurface = $surfacePalettes['dark'] ?? [];
    $lightSurface = $surfacePalettes['light'] ?? [];
    $darkText = $textPalettes['dark'] ?? [];
    $lightText = $textPalettes['light'] ?? [];

    $fallbacks = [
        'background_dark' => '#0b1120',
        'background_light' => '#f4f4f5',
        'body_dark' => '#111827',
        'body_light' => '#f8fafc',
        'card_dark' => '#101828',
        'card_light' => '#ffffff',
        'text_dark' => '#e2e8f0',
        'text_light' => '#0f172a',
        'muted_dark' => '#94a3b8',
        'muted_light' => '#475569',
        'accent_dark' => '#38bdf8',
        'accent_light' => '#0ea5e9',
        'divider_dark' => 'rgba(148, 163, 184, 0.35)',
        'divider_light' => 'rgba(148, 163, 184, 0.45)',
    ];

    $darkBackground = $darkSurface['background'] ?? $darkPalette['background'] ?? $fallbacks['background_dark'];
    $lightBackground = $lightSurface['background'] ?? $lightPalette['background'] ?? $fallbacks['background_light'];
    $darkBody = $darkSurface['body'] ?? $darkPalette['body'] ?? $fallbacks['body_dark'];
    $lightBody = $lightSurface['body'] ?? $lightPalette['body'] ?? $fallbacks['body_light'];
    $darkCard = $darkSurface['card'] ?? $fallbacks['card_dark'];
    $lightCard = $lightSurface['card'] ?? $fallbacks['card_light'];
    $darkTextPrimary = $darkText['primary'] ?? $darkPalette['text_primary'] ?? $fallbacks['text_dark'];
    $lightTextPrimary = $lightText['primary'] ?? $lightPalette['text_primary'] ?? $fallbacks['text_light'];
    $darkTextMuted = $darkText['muted'] ?? $darkPalette['muted_text'] ?? $fallbacks['muted_dark'];
    $lightTextMuted = $lightText['muted'] ?? $lightPalette['muted_text'] ?? $fallbacks['muted_light'];
    $darkAccent = $darkPalette['accent_primary'] ?? $darkPalette['primary'] ?? $fallbacks['accent_dark'];
    $lightAccent = $lightPalette['accent_primary'] ?? $lightPalette['primary'] ?? $fallbacks['accent_light'];
    $darkSecondary = $darkPalette['secondary'] ?? 'rgba(15, 23, 42, 0.85)';
    $lightSecondary = $lightPalette['secondary'] ?? 'rgba(241, 245, 249, 0.9)';
    $darkDivider = $fallbacks['divider_dark'];
    $lightDivider = $fallbacks['divider_light'];
@endphp
<!DOCTYPE html>
<html lang="en" data-theme="{{ $initialMode }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ $document->title }} · {{ config('app.name') }}</title>
        <style>
            :root {
                color-scheme: light dark;
                --legal-background-dark: {{ $darkBackground }};
                --legal-background-light: {{ $lightBackground }};
                --legal-body-dark: {{ $darkBody }};
                --legal-body-light: {{ $lightBody }};
                --legal-card-dark: {{ $darkCard }};
                --legal-card-light: {{ $lightCard }};
                --legal-text-dark: {{ $darkTextPrimary }};
                --legal-text-light: {{ $lightTextPrimary }};
                --legal-muted-dark: {{ $darkTextMuted }};
                --legal-muted-light: {{ $lightTextMuted }};
                --legal-accent-dark: {{ $darkAccent }};
                --legal-accent-light: {{ $lightAccent }};
                --legal-secondary-dark: {{ $darkSecondary }};
                --legal-secondary-light: {{ $lightSecondary }};
                --legal-divider-dark: {{ $darkDivider }};
                --legal-divider-light: {{ $lightDivider }};
            }

            html {
                font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, 'Helvetica Neue', sans-serif;
                min-height: 100%;
            }

            html[data-theme='dark'] {
                --legal-background: var(--theme-background, var(--legal-background-dark));
                --legal-body: var(--theme-body, var(--legal-body-dark));
                --legal-card: var(--theme-surface-card, var(--legal-card-dark));
                --legal-text: var(--theme-text, var(--legal-text-dark));
                --legal-muted: var(--theme-text-muted, var(--legal-muted-dark));
                --legal-accent: var(--theme-accent, var(--legal-accent-dark));
                --legal-secondary: var(--theme-secondary, var(--legal-secondary-dark));
                --legal-divider: var(--theme-divider, var(--legal-divider-dark));
            }

            html[data-theme='light'] {
                --legal-background: var(--theme-background, var(--legal-background-light));
                --legal-body: var(--theme-body, var(--legal-body-light));
                --legal-card: var(--theme-surface-card, var(--legal-card-light));
                --legal-text: var(--theme-text, var(--legal-text-light));
                --legal-muted: var(--theme-text-muted, var(--legal-muted-light));
                --legal-accent: var(--theme-accent, var(--legal-accent-light));
                --legal-secondary: var(--theme-secondary, var(--legal-secondary-light));
                --legal-divider: var(--theme-divider, var(--legal-divider-light));
            }

            body {
                margin: 0;
                padding: 3rem 1rem 4rem;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                background: var(--legal-background);
                color: var(--legal-text);
            }

            .legal-container {
                width: 100%;
                max-width: 760px;
                border-radius: 24px;
                padding: 2.75rem;
                background: var(--legal-card);
                border: 1px solid var(--legal-accent);
                border-color: color-mix(in srgb, var(--legal-accent) 25%, transparent);
                box-shadow: 0 35px 80px rgba(15, 23, 42, 0.45);
                box-shadow: 0 35px 80px color-mix(in srgb, var(--legal-background) 45%, transparent);
            }

            h1 {
                margin-top: 0;
                font-size: clamp(1.85rem, 2.4vw, 3rem);
                line-height: 1.15;
                color: var(--legal-text);
            }

            .legal-content {
                margin-top: 1.75rem;
                font-size: 1.05rem;
                line-height: 1.85;
                color: var(--legal-text);
                opacity: 0.92;
                white-space: pre-line;
            }

            .meta {
                font-size: 0.85rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: var(--legal-muted);
                margin-bottom: 0.75rem;
            }

            a {
                color: var(--legal-accent);
                text-decoration: none;
                transition: color 150ms ease;
            }

            a:hover,
            a:focus-visible {
                color: var(--legal-accent);
                color: color-mix(in srgb, var(--legal-accent) 75%, #ffffff 25%);
                outline: none;
            }

            .legal-footer {
                margin-top: 2.5rem;
                display: inline-flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.45rem 1.1rem;
                border-radius: 999px;
                background: var(--legal-secondary);
                background: color-mix(in srgb, var(--legal-secondary) 90%, transparent);
                border: 1px solid var(--legal-divider);
                font-size: 0.85rem;
                color: var(--legal-text);
            }

            .legal-footer span {
                opacity: 0.6;
            }

            @media (max-width: 640px) {
                body {
                    padding: 2rem 1rem;
                }

                .legal-container {
                    border-radius: 18px;
                    padding: 2rem;
                }
            }
        </style>
    </head>
    <body>
        <main class="legal-container">
            <div class="meta">{{ config('app.name') }}</div>
            <h1>{{ $document->title }}</h1>
            <article class="legal-content">{!! nl2br(e($document->content)) !!}</article>
            <footer class="legal-footer">
                <a href="https://darkoak.eu" target="_blank" rel="noopener noreferrer">Powered by DarkOak.eu</a>
                <span>|</span>
                <a href="/legal/terms-of-service">Terms of Service</a>
                <span>|</span>
                <a href="/legal/legal-notice">Legal Notice</a>
            </footer>
        </main>
    </body>
</html>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="color-scheme" content="dark light" />
        <meta name="supported-color-schemes" content="dark light" />
    <title>{{ config('app.name', 'DarkOaktyl') }}</title>
        @php
            $darkPalette = $palettes['dark'] ?? [];
            $lightPalette = $palettes['light'] ?? $darkPalette;
            $themePrimary = $theme->primary_color ?? '#2563eb';
            $themeSecondary = $theme->secondary_color ?? '#1e40af';
            $lightPrimary = $lightPalette['primary_color'] ?? $themePrimary;
            $lightSecondary = $lightPalette['secondary_color'] ?? $themeSecondary;
        @endphp
        <style>
            :root {
                color-scheme: dark light;
                supported-color-schemes: dark light;
            }

            body[data-color-mode='responsive'] {
                margin: 0;
                padding: 0;
                background-color: {{ $theme->background_color ?? '#0f172a' }};
                color: {{ $theme->text_color ?? '#0f172a' }};
                font-family: 'Helvetica Neue', Arial, sans-serif;
            }

            .email-wrapper {
                background-color: {{ $theme->background_color ?? '#0f172a' }};
                padding: 32px 0;
            }

            .email-card {
                background-color: {{ $theme->body_color ?? '#ffffff' }};
                color: {{ $theme->text_color ?? '#0f172a' }};
            }

            .email-footer {
                color: {{ $theme->muted_text_color ?? '#475569' }};
            }

            .email-button {
                background-color: {{ $theme->button_color ?? '#2563eb' }};
                color: {{ $theme->button_text_color ?? '#ffffff' }};
            }

            @media (prefers-color-scheme: light) {
                body[data-color-mode='responsive'] {
                    background-color: {{ $lightPalette['background_color'] ?? '#ffffff' }} !important;
                    color: {{ $lightPalette['text_color'] ?? '#0f172a' }} !important;
                }

                .email-wrapper {
                    background-color: {{ $lightPalette['background_color'] ?? '#ffffff' }} !important;
                }

                .email-card {
                    background-color: {{ $lightPalette['body_color'] ?? '#f3f4f6' }} !important;
                    color: {{ $lightPalette['text_color'] ?? '#0f172a' }} !important;
                }

                .email-header {
                    background: linear-gradient(135deg, {{ $lightPrimary }}, {{ $lightSecondary }}) !important;
                    color: #0f172a !important;
                }

                .email-footer {
                    color: {{ $lightPalette['muted_text_color'] ?? '#475569' }} !important;
                }

                .email-button {
                    background-color: {{ $lightPalette['button_color'] ?? '#4338ca' }} !important;
                    color: {{ $lightPalette['button_text_color'] ?? '#111827' }} !important;
                }
            }

            @media (prefers-color-scheme: dark) {
                body[data-color-mode='responsive'] {
                    background-color: {{ $darkPalette['background_color'] ?? ($theme->background_color ?? '#0f172a') }} !important;
                    color: {{ $darkPalette['text_color'] ?? ($theme->text_color ?? '#f8fafc') }} !important;
                }

                .email-wrapper {
                    background-color: {{ $darkPalette['background_color'] ?? ($theme->background_color ?? '#0f172a') }} !important;
                }

                .email-card {
                    background-color: {{ $darkPalette['body_color'] ?? ($theme->body_color ?? '#111827') }} !important;
                    color: {{ $darkPalette['text_color'] ?? ($theme->text_color ?? '#f8fafc') }} !important;
                }

                .email-header {
                    background: linear-gradient(135deg, {{ $themePrimary }}, {{ $themeSecondary }}) !important;
                    color: #ffffff !important;
                }

                .email-footer {
                    color: {{ $darkPalette['muted_text_color'] ?? ($theme->muted_text_color ?? '#9ca3af') }} !important;
                }

                .email-button {
                    background-color: {{ $darkPalette['button_color'] ?? ($theme->button_color ?? '#2563eb') }} !important;
                    color: {{ $darkPalette['button_text_color'] ?? ($theme->button_text_color ?? '#ffffff') }} !important;
                }
            }
        </style>
    </head>
    <body data-color-mode="responsive" style="margin:0;padding:0;background-color:{{ $theme->background_color ?? '#0f172a' }};color:{{ $theme->text_color ?? '#0f172a' }};font-family:'Helvetica Neue',Arial,sans-serif;">
        <table width="100%" cellpadding="0" cellspacing="0" class="email-wrapper" role="presentation">
            <tr>
                <td align="center">
                    <table
                        width="640"
                        cellpadding="0"
                        cellspacing="0"
                        class="email-card"
                        role="presentation"
                        style="max-width:640px;width:100%;border-radius:12px;overflow:hidden;box-shadow:0 8px 32px rgba(15,23,42,0.25);"
                    >
                        <tr>
                            <td class="email-header" style="padding:32px 40px 24px;background:linear-gradient(135deg, {{ $theme->primary_color ?? '#2563eb' }}, {{ $theme->secondary_color ?? '#1e40af' }});color:#ffffff;">
                                <div style="text-align:left;">
                                    @if(!empty($theme->logo_url))
                                        <img src="{{ $theme->logo_url }}" alt="{{ config('app.name') }}" style="max-height:48px;display:block;margin-bottom:16px;" />
                                    @endif
                                    <h1 style="margin:0;font-size:24px;line-height:1.4;font-weight:600;">{{ config('app.name', 'DarkOaktyl') }}</h1>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="email-body" style="padding:32px 40px;color:{{ $theme->text_color ?? '#0f172a' }};font-size:16px;line-height:1.6;">
                                {!! $content !!}
                            </td>
                        </tr>
                        @if(!empty($theme->footer_text))
                            <tr>
                                <td class="email-footer" style="padding:24px 40px;background-color:{{ $theme->body_color ?? '#ffffff' }};border-top:1px solid rgba(15,23,42,0.08);font-size:13px;text-align:center;">
                                    {!! nl2br(e($theme->footer_text)) !!}
                                </td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>

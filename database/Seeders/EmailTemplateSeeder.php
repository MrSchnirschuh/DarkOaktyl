<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Everest\Models\Setting;
use Everest\Models\EmailTheme;
use Everest\Models\EmailTemplate;
use Everest\Models\EmailTrigger;
use Everest\Services\Themes\ThemePaletteService;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        /** @var ThemePaletteService $paletteService */
        $paletteService = app(ThemePaletteService::class);

        $defaults = $paletteService->getEmailDefaults();
        $palettes = $paletteService->getEmailPalettes();

        $dark = $palettes['dark'] ?? [];
        $light = $palettes['light'] ?? [];

        $defaultLightPalette = Arr::get($defaults, 'light_palette', []);

        $defaultData = [
            'name' => Arr::get($defaults, 'name', 'DarkOaktyl Default'),
            'description' => 'Default theme applied to outgoing panel emails.',
            'primary_color' => Arr::get($defaults, 'primary_color', Arr::get($dark, 'primary_color', '#2563eb')),
            'secondary_color' => Arr::get($defaults, 'secondary_color', Arr::get($dark, 'secondary_color', '#1e40af')),
            'accent_color' => Arr::get($defaults, 'accent_color', Arr::get($dark, 'accent_color', '#f97316')),
            'background_color' => Arr::get($defaults, 'background_color', Arr::get($dark, 'background_color', '#0f172a')),
            'body_color' => Arr::get($defaults, 'body_color', Arr::get($dark, 'body_color', '#111827')),
            'text_color' => Arr::get($defaults, 'text_color', Arr::get($dark, 'text_color', '#0f172a')),
            'muted_text_color' => Arr::get($defaults, 'muted_text_color', Arr::get($dark, 'muted_text_color', '#475569')),
            'button_color' => Arr::get($defaults, 'button_color', Arr::get($dark, 'button_color', '#2563eb')),
            'button_text_color' => Arr::get($defaults, 'button_text_color', Arr::get($dark, 'button_text_color', '#ffffff')),
            'footer_text' => Arr::get($defaults, 'footer_text') ?: ('© ' . now()->year . ' ' . config('app.name', 'DarkOaktyl') . '.'),
            'is_default' => true,
            'meta' => array_merge(['appearance' => 'system'], (array) Arr::get($defaults, 'meta', [])),
            'variant_mode' => 'dual',
            'light_palette' => !empty($defaultLightPalette) ? $defaultLightPalette : [
                'primary' => Arr::get($light, 'primary_color', '#2563eb'),
                'secondary' => Arr::get($light, 'secondary_color', '#1e40af'),
                'accent' => Arr::get($light, 'accent_color', '#f97316'),
                'background' => Arr::get($light, 'background_color', '#f8fafc'),
                'body' => Arr::get($light, 'body_color', '#ffffff'),
                'text' => Arr::get($light, 'text_color', '#0f172a'),
                'muted' => Arr::get($light, 'muted_text_color', '#475569'),
                'button' => Arr::get($light, 'button_color', '#2563eb'),
                'button_text' => Arr::get($light, 'button_text_color', '#ffffff'),
            ],
        ];

        $defaultTheme = EmailTheme::query()
            ->where('name', $defaultData['name'])
            ->orWhere('name', 'Everest Default')
            ->orWhere('is_default', true)
            ->first();

        if (!$defaultTheme) {
            $defaultTheme = new EmailTheme(['name' => $defaultData['name']]);
        }

        $defaultTheme->fill($defaultData);
        $defaultTheme->is_default = true;
        $defaultTheme->variant_mode = 'dual';
        $defaultTheme->meta = $defaultData['meta'];
        $defaultTheme->light_palette = $defaultData['light_palette'];
        $defaultTheme->save();

        EmailTheme::query()
            ->where('id', '!=', $defaultTheme->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        $variantThemes = [
            'DarkOaktyl Dark' => [
                'palette' => $dark,
                'appearance' => 'dark',
                'description' => 'Dark variant of the DarkOaktyl palette for outgoing emails.',
            ],
            'DarkOaktyl Light' => [
                'palette' => $light,
                'appearance' => 'light',
                'description' => 'Light variant of the DarkOaktyl palette for outgoing emails.',
            ],
        ];

        foreach ($variantThemes as $name => $variantConfig) {
            $palette = $variantConfig['palette'];

            $variant = EmailTheme::query()->firstOrNew(['name' => $name]);
            $variant->fill([
                'description' => $variantConfig['description'],
                'primary_color' => Arr::get($palette, 'primary_color', '#2563eb'),
                'secondary_color' => Arr::get($palette, 'secondary_color', '#1e40af'),
                'accent_color' => Arr::get($palette, 'accent_color', '#f97316'),
                'background_color' => Arr::get($palette, 'background_color', $name === 'DarkOaktyl Light' ? '#f8fafc' : '#0f172a'),
                'body_color' => Arr::get($palette, 'body_color', $name === 'DarkOaktyl Light' ? '#ffffff' : '#111827'),
                'text_color' => Arr::get($palette, 'text_color', $name === 'DarkOaktyl Light' ? '#0f172a' : '#0f172a'),
                'muted_text_color' => Arr::get($palette, 'muted_text_color', '#475569'),
                'button_color' => Arr::get($palette, 'button_color', '#2563eb'),
                'button_text_color' => Arr::get($palette, 'button_text_color', '#ffffff'),
                'footer_text' => Arr::get($defaults, 'footer_text') ?: ('© ' . now()->year . ' ' . config('app.name', 'DarkOaktyl') . '.'),
                'is_default' => false,
                'meta' => ['appearance' => $variantConfig['appearance']],
                'variant_mode' => 'single',
                'light_palette' => null,
            ]);
            $variant->save();
        }

        Setting::query()->updateOrCreate(
            ['key' => 'settings::modules:email:default_theme'],
            ['value' => $defaultTheme->uuid]
        );

        Setting::query()->updateOrCreate(
            ['key' => 'settings::modules:email:enabled'],
            ['value' => config('modules.email.enabled', true) ? 'true' : 'false']
        );

        $templates = [
            [
                'key' => 'auth.password_reset',
                'name' => 'Password reset',
                'subject' => 'Reset your {{ config(\'app.name\') }} password',
                'description' => 'Sent to users when they request a password reset.',
                'content' => <<<'MD'
# Reset your password

Hello {{ $user->username ?? $user->email }},

We received a request to reset your panel password. Click the button below to continue.

[Reset Password]({{ $actionUrl ?? $resetUrl ?? '#' }})

If you did not request this change, you can safely ignore this email.

Thanks,
{{ config('app.name') }}
MD,
            ],
            [
                'key' => 'auth.email_verification',
                'name' => 'Email verification',
                'subject' => 'Verify your email for {{ config(\'app.name\') }}',
                'description' => 'Sent to users after registration to confirm their address.',
                'content' => <<<'MD'
# Verify your email address

Hi {{ $user->username ?? 'there' }},

Thanks for creating an account with {{ config('app.name') }}. Confirm your address by clicking the button below.

[Verify Email]({{ $verificationUrl ?? '#' }})

If you did not create this account, no further action is required.

Stay awesome,
{{ config('app.name') }}
MD,
            ],
            [
                'key' => 'servers.provisioned',
                'name' => 'Server created',
                'subject' => 'Your new server {{ $server->name ?? \'is ready\' }}',
                'description' => 'Sent to users when a server has finished provisioning.',
                'content' => <<<'MD'
# Your server is live

{{ $user->username ?? 'Hey there' }},

The server **{{ $server->name ?? 'Unnamed Server' }}** is ready to use. You can manage it from your panel dashboard at any time.

**Connection Information**

- Address: {{ $server->allocation->ip ?? 'pending' }}
- Port: {{ $server->allocation->port ?? 'pending' }}

[Open the Panel]({{ $panelUrl ?? config('app.url') }})

Happy hosting!
{{ config('app.name') }} Team
MD,
            ],
            [
                'key' => 'billing.coupon_holiday',
                'name' => 'Holiday promotion coupon',
                'subject' => 'A holiday gift from {{ config(\'app.name\') }}',
                'description' => 'Example promotional email containing a coupon code.',
                'content' => <<<'MD'
# A holiday gift just for you

We appreciate you being part of the {{ config('app.name') }} community.

Use the coupon code **{{ $couponCode ?? 'HAPPY-HOLIDAYS' }}** at checkout to receive {{ $discount ?? '20% off' }} your next order.

This code is valid until {{ $expiresAt ?? now()->addDays(14)->toFormattedDateString() }}.

Share the joy and happy gaming!
{{ config('app.name') }}
MD,
            ],
        ];

        foreach ($templates as $templateData) {
            EmailTemplate::query()->updateOrCreate(
                ['key' => $templateData['key']],
                [
                    'name' => $templateData['name'],
                    'subject' => $templateData['subject'],
                    'description' => $templateData['description'],
                    'content' => $templateData['content'],
                    'theme_id' => $defaultTheme->id,
                    'locale' => 'en',
                    'is_enabled' => true,
                    'metadata' => [],
                ]
            );
        }

        $template = EmailTemplate::query()->where('key', 'billing.coupon_holiday')->first();
        if ($template) {
            EmailTrigger::query()->updateOrCreate(
                ['name' => 'Holiday coupon broadcast'],
                [
                    'description' => 'Example scheduled trigger that sends a coupon code every year on December 1st.',
                    'trigger_type' => EmailTrigger::TYPE_SCHEDULE,
                    'schedule_type' => EmailTrigger::SCHEDULE_RECURRING,
                    'cron_expression' => '0 9 1 12 *',
                    'timezone' => config('app.timezone', 'UTC'),
                    'template_id' => $template->id,
                    'payload' => [
                        'audience' => ['type' => 'all_users'],
                        'data' => [
                            'couponCode' => 'HAPPY-HOLIDAYS',
                            'discount' => '25% off',
                            'expiresAt' => now()->setMonth(12)->setDay(31)->format('F j, Y'),
                        ],
                    ],
                    'is_active' => false,
                    'next_run_at' => null,
                ]
            );
        }
    }
}

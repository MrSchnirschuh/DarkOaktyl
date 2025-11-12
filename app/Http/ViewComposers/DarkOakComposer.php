<?php

namespace DarkOak\Http\ViewComposers;

use Illuminate\View\View;
use DarkOak\Services\Themes\ThemePaletteService;

class DarkOakComposer
{
    public function __construct(private ThemePaletteService $paletteService)
    {
    }

    /**
     * Provide access to the asset service in the views.
     */
    public function compose(View $view): void
    {
        $emailDefaults = $this->paletteService->getEmailDefaults();

        $view->with('DarkOakConfiguration', [
            'auth' => [
                'registration' => [
                    'enabled' => boolval(config('modules.auth.registration.enabled', false)),
                ],
                'security' => [
                    'force2fa' => boolval(config('modules.auth.security.force2fa', false)),
                    'attempts' => config('modules.auth.security.attempts', 3),
                ],
                'modules' => [
                    'discord' => [
                        'enabled' => boolval(config('modules.auth.discord.enabled', false)),
                        'clientId' => !empty(config('modules.auth.discord.client_id')),
                        'clientSecret' => !empty(config('modules.auth.discord.client_secret')),
                    ],
                    'google' => [
                        'enabled' => boolval(config('modules.auth.google.enabled', false)),
                        'clientId' => !empty(config('modules.auth.google.client_id', false)),
                        'clientSecret' => !empty(config('modules.auth.google.client_secret')),
                    ],
                    'onboarding' => [
                        'enabled' => boolval(config('modules.auth.onboarding.enabled', false)),
                        'content' => config('modules.auth.onboarding.content', ''),
                    ],
                    'jguard' => [
                        'enabled' => boolval(config('modules.auth.jguard.enabled', false)),
                    ],
                ],
            ],
            'tickets' => [
                'enabled' => boolval(config('modules.tickets.enabled', false)),
                'maxCount' => config('modules.tickets.max_count', 3),
            ],
            'billing' => [
                'enabled' => boolval(config('modules.billing.enabled', false)),
                'paypal' => config('modules.billing.paypal'),
                'link' => config('modules.billing.link'),
                'keys' => [
                    'publishable' => boolval(config('modules.billing.keys.publishable')),
                    'secret' => boolval(config('modules.billing.keys.secret')),
                ],
                'currency' => [
                    'symbol' => config('modules.billing.currency.symbol'),
                    'code' => config('modules.billing.currency.code'),
                ],
            ],
            'emails' => [
                'enabled' => boolval(config('modules.email.enabled', false)),
                'defaultTheme' => config('modules.email.default_theme'),
                'defaults' => $emailDefaults,
            ],
            'alert' => [
                'enabled' => boolval(config('modules.alert.enabled', false)),
                'type' => config('modules.alert.type'),
                'position' => config('modules.alert.position'),
                'content' => config('modules.alert.content'),
                'uuid' => config('modules.alert.uuid'),
            ],
            'ai' => [
                'enabled' => boolval(config('modules.ai.enabled', false)),
                'key' => !empty(config('modules.ai.key')),
                'user_access' => boolval(config('modules.ai.user_access', false)),
            ],
            'webhooks' => [
                'enabled' => boolval(config('modules.webhooks.enabled', false)),
                'url' => !empty(config('modules.webhooks.url')),
            ],
        ]);
    }
}


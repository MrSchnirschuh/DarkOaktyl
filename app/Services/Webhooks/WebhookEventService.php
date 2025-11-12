<?php

namespace DarkOak\Services\Webhooks;

use DarkOak\Models\User;
use DarkOak\Models\WebhookEvent;
use Illuminate\Support\Facades\Http;
use DarkOak\Exceptions\DisplayException;
use DarkOak\Contracts\Repository\ThemeRepositoryInterface;
use DarkOak\Contracts\Repository\SettingsRepositoryInterface;

class WebhookEventService
{
    /**
     * WebhookEventService constructor.
     */
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private ThemeRepositoryInterface $theme,
    ) {
    }

    /**
     * Send a webhook through the defined URL.
     *
     * @throws \Exception
     */
    public function send(User $user, WebhookEvent $event): void
    {
        $color = $this->theme->get('theme::colors:primary');
        $url = $this->settings->get('settings::modules:webhooks:url');

        if (!$url) {
            throw new DisplayException('No Webhook URL has been defined.');
        }

        try {
            Http::post($url, [
                'embeds' => [[
                    'title' => $event->key,
                    'description' => $event->description,
                    'url' => env('APP_URL') . '/admin',
                    'color' => $color,
                    'timestamp' => now()->toIso8601String(),
                    'footer' => [
                        'text' => 'DarkOaktyl v4',
                        'icon_url' => 'https://avatars.githubusercontent.com/u/91636558?s=200&v=4',
                    ],
                    'author' => [
                        'name' => $user->email,
                        'url' => env('APP_URL') . '/admin/users/' . $user->id,
                    ],
                ]],
            ]);
        } catch (DisplayException $ex) {
            throw new DisplayException('Unable to send webhook through URL.');
        }
    }
}



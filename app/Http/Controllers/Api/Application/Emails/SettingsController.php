<?php

namespace DarkOak\Http\Controllers\Api\Application\Emails;

use DarkOak\Facades\Activity;
use DarkOak\Models\EmailTheme;
use DarkOak\Models\EmailTemplate;
use DarkOak\Models\EmailTrigger;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Contracts\Repository\SettingsRepositoryInterface;
use DarkOak\Http\Requests\Api\Application\Emails\GetEmailSettingsRequest;
use DarkOak\Http\Requests\Api\Application\Emails\UpdateEmailSettingsRequest;
use DarkOak\Services\Themes\ThemePaletteService;

class SettingsController extends ApplicationApiController
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private ThemePaletteService $paletteService
    ) {
        parent::__construct();
    }

    public function index(GetEmailSettingsRequest $request): array
    {
        return [
            'enabled' => (bool) config('modules.email.enabled', false),
            'default_theme' => config('modules.email.default_theme'),
            'defaults' => $this->paletteService->getEmailDefaults(),
            'counts' => [
                'themes' => EmailTheme::query()->count(),
                'templates' => EmailTemplate::query()->count(),
                'triggers' => EmailTrigger::query()->count(),
            ],
        ];
    }

    public function update(UpdateEmailSettingsRequest $request): Response
    {
        $key = $request->input('key');
        $value = $request->input('value');

        $handled = false;

        if ($key === 'enabled') {
            $handled = true;

            $enabled = filter_var($value, FILTER_VALIDATE_BOOLEAN);

            $this->settings->set('settings::modules:email:enabled', $enabled ? 'true' : 'false');
            config(['modules.email.enabled' => $enabled]);
        } elseif ($key === 'default_theme') {
            $handled = true;

            $theme = null;

            if (!empty($value)) {
                $theme = EmailTheme::query()->where('uuid', $value)->firstOrFail();
            }

            DB::transaction(function () use ($theme) {
                $this->applyDefaultTheme($theme);
            });
        }

        if ($handled) {
            Activity::event('admin:emails:settings:update')
                ->property('key', $key)
                ->property('value', $value)
                ->description('Email module settings were updated')
                ->log();
        }

        return $this->returnNoContent();
    }

    private function applyDefaultTheme(?EmailTheme $theme): void
    {
        if ($theme) {
            EmailTheme::query()
                ->where('id', '!=', $theme->id)
                ->update(['is_default' => false]);

            $theme->forceFill(['is_default' => true])->save();

            $this->settings->set('settings::modules:email:default_theme', $theme->uuid);
            config(['modules.email.default_theme' => $theme->uuid]);

            return;
        }

        EmailTheme::query()->update(['is_default' => false]);

        $this->settings->set('settings::modules:email:default_theme', '');
        config(['modules.email.default_theme' => null]);
    }
}


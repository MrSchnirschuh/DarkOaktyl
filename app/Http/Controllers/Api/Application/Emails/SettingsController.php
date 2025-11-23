<?php

namespace DarkOak\Http\Controllers\Api\Application\Emails;

use DarkOak\Facades\Activity;
use DarkOak\Models\EmailTheme;
use DarkOak\Models\EmailTemplate;
use DarkOak\Models\EmailTrigger;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use DarkOak\Exceptions\DarkOaktylException;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Contracts\Repository\SettingsRepositoryInterface;
use DarkOak\Http\Requests\Api\Application\Emails\GetEmailSettingsRequest;
use DarkOak\Http\Requests\Api\Application\Emails\UpdateEmailSettingsRequest;
use DarkOak\Http\Requests\Api\Application\Emails\UpdateEmailEnvironmentRequest;
use DarkOak\Services\Themes\ThemePaletteService;
use DarkOak\Traits\Commands\EnvironmentWriterTrait;

class SettingsController extends ApplicationApiController
{
    use EnvironmentWriterTrait;

    private const MAIL_ENVIRONMENT_MAP = [
        'mailer' => 'MAIL_MAILER',
        'host' => 'MAIL_HOST',
        'port' => 'MAIL_PORT',
        'username' => 'MAIL_USERNAME',
        'password' => 'MAIL_PASSWORD',
        'encryption' => 'MAIL_ENCRYPTION',
        'from_address' => 'MAIL_FROM_ADDRESS',
        'from_name' => 'MAIL_FROM_NAME',
    ];

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
            'environment' => $this->getMailEnvironment(),
            'mailers' => array_keys(config('mail.mailers', [])),
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

    public function updateEnvironment(UpdateEmailEnvironmentRequest $request): Response
    {
        try {
            $this->persistMailEnvironment($request->validated());
        } catch (DarkOaktylException $exception) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }

        Activity::event('admin:emails:settings:update')
            ->property('key', 'environment')
            ->description('Email transport environment values were updated')
            ->log();

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

    private function getMailEnvironment(): array
    {
        $mailPort = config('mail.mailers.smtp.port');

        return [
            'mailer' => config('mail.default', 'smtp'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => is_null($mailPort) ? null : (string) $mailPort,
            'username' => config('mail.mailers.smtp.username'),
            'password' => config('mail.mailers.smtp.password'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ];
    }

    private function persistMailEnvironment(array $payload): void
    {
        $updates = [];

        foreach (self::MAIL_ENVIRONMENT_MAP as $field => $envKey) {
            if (array_key_exists($field, $payload)) {
                $value = $payload[$field];
                $updates[$envKey] = is_null($value) ? '' : (string) $value;
            }
        }

        if (empty($updates)) {
            return;
        }

        $this->writeToEnvironment($updates);

        if (array_key_exists('MAIL_MAILER', $updates)) {
            config(['mail.default' => $updates['MAIL_MAILER'] ?: 'smtp']);
        }

        if (array_key_exists('MAIL_HOST', $updates)) {
            config(['mail.mailers.smtp.host' => $updates['MAIL_HOST'] ?: null]);
        }

        if (array_key_exists('MAIL_PORT', $updates)) {
            config(['mail.mailers.smtp.port' => $updates['MAIL_PORT'] ?: null]);
        }

        if (array_key_exists('MAIL_USERNAME', $updates)) {
            config(['mail.mailers.smtp.username' => $updates['MAIL_USERNAME'] ?: null]);
        }

        if (array_key_exists('MAIL_PASSWORD', $updates)) {
            config(['mail.mailers.smtp.password' => $updates['MAIL_PASSWORD'] ?: null]);
        }

        if (array_key_exists('MAIL_ENCRYPTION', $updates)) {
            config(['mail.mailers.smtp.encryption' => $updates['MAIL_ENCRYPTION'] ?: null]);
        }

        if (array_key_exists('MAIL_FROM_ADDRESS', $updates)) {
            config(['mail.from.address' => $updates['MAIL_FROM_ADDRESS'] ?: null]);
        }

        if (array_key_exists('MAIL_FROM_NAME', $updates)) {
            config(['mail.from.name' => $updates['MAIL_FROM_NAME'] ?: null]);
        }
    }
}


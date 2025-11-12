<?php

namespace DarkOak\Http\Controllers\Api\Application\Emails;

use DarkOak\Facades\Activity;
use DarkOak\Models\EmailTheme;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Contracts\Repository\SettingsRepositoryInterface;
use DarkOak\Http\Requests\Api\Application\Emails\DeleteEmailThemeRequest;
use DarkOak\Http\Requests\Api\Application\Emails\GetEmailThemesRequest;
use DarkOak\Http\Requests\Api\Application\Emails\StoreEmailThemeRequest;
use DarkOak\Http\Requests\Api\Application\Emails\UpdateEmailThemeRequest;
use DarkOak\Transformers\Api\Application\Emails\EmailThemeTransformer;
use DarkOak\Exceptions\Http\QueryValueOutOfRangeHttpException;

class ThemeController extends ApplicationApiController
{
    public function __construct(private SettingsRepositoryInterface $settings)
    {
        parent::__construct();
    }

    public function index(GetEmailThemesRequest $request): array
    {
        $perPage = (int) $request->query('per_page', '50');
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $themes = QueryBuilder::for(EmailTheme::query())
            ->allowedFilters(['name', 'is_default'])
            ->allowedSorts(['name', 'created_at', 'updated_at'])
            ->paginate($perPage);

        return $this->fractal->collection($themes)
            ->transformWith(EmailThemeTransformer::class)
            ->toArray();
    }

    public function store(StoreEmailThemeRequest $request): JsonResponse
    {
        $payload = $this->extractThemeAttributes($request);

        $variant = $request->input('variant_mode', 'single');
        $payload['variant_mode'] = $variant;
        $payload['light_palette'] = $variant === 'dual'
            ? $this->sanitizePalette($request->input('light_palette', []))
            : null;

        $shouldSetDefault = $request->boolean('set_default') || !EmailTheme::query()->where('is_default', true)->exists();

        $payload['is_default'] = $shouldSetDefault;
        $payload['meta'] = [];

        $theme = null;

        DB::transaction(function () use (&$theme, $payload, $shouldSetDefault) {
            $theme = EmailTheme::query()->create($payload);

            if ($shouldSetDefault) {
                $this->setDefaultTheme($theme);
            }
        });

        Activity::event('admin:emails:themes:create')
            ->property('theme', $theme)
            ->description('An email theme was created')
            ->log();

        return $this->fractal->item($theme)
            ->transformWith(EmailThemeTransformer::class)
            ->respond(Response::HTTP_CREATED);
    }

    public function view(GetEmailThemesRequest $request, EmailTheme $theme): array
    {
        return $this->fractal->item($theme)
            ->transformWith(EmailThemeTransformer::class)
            ->toArray();
    }

    public function update(UpdateEmailThemeRequest $request, EmailTheme $theme): array
    {
        $attributes = $this->extractThemeAttributes($request);
        $shouldSetDefault = $request->boolean('set_default');

        $variant = $request->input('variant_mode', $theme->variant_mode ?? 'single');
        $attributes['variant_mode'] = $variant;
        $attributes['light_palette'] = $variant === 'dual'
            ? $this->sanitizePalette($request->input('light_palette', $theme->light_palette ?? []))
            : null;

        DB::transaction(function () use ($theme, $attributes, $shouldSetDefault) {
            if (!empty($attributes)) {
                $theme->updateOrFail($attributes);
            }

            if ($shouldSetDefault) {
                $this->setDefaultTheme($theme);
            }
        });

        Activity::event('admin:emails:themes:update')
            ->property('theme', $theme)
            ->property('changes', $attributes)
            ->description('An email theme was updated')
            ->log();

        return $this->fractal->item($theme->refresh())
            ->transformWith(EmailThemeTransformer::class)
            ->toArray();
    }

    public function delete(DeleteEmailThemeRequest $request, EmailTheme $theme): Response
    {
        $wasDefault = $theme->is_default;

        DB::transaction(function () use ($theme, $wasDefault) {
            $theme->delete();

            if ($wasDefault) {
                $replacement = EmailTheme::query()->orderBy('created_at')->first();

                $this->setDefaultTheme($replacement);
            }
        });

        Activity::event('admin:emails:themes:delete')
            ->property('theme', $theme)
            ->description('An email theme was deleted')
            ->log();

        return $this->returnNoContent();
    }

    private function extractThemeAttributes(StoreEmailThemeRequest|UpdateEmailThemeRequest $request): array
    {
        $fields = [
            'name',
            'description',
            'primary_color',
            'secondary_color',
            'accent_color',
            'background_color',
            'body_color',
            'text_color',
            'muted_text_color',
            'button_color',
            'button_text_color',
            'logo_url',
            'footer_text',
        ];

        $attributes = [];
        foreach ($fields as $field) {
            if ($request->exists($field)) {
                $attributes[$field] = $request->input($field);
            }
        }

        return $attributes;
    }

    private function sanitizePalette(array $palette): array
    {
        $keys = [
            'primary',
            'secondary',
            'accent',
            'background',
            'body',
            'text',
            'muted',
            'button',
            'button_text',
        ];

        $sanitized = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $palette)) {
                $sanitized[$key] = $palette[$key];
            }
        }

        return $sanitized;
    }

    private function setDefaultTheme(?EmailTheme $theme): void
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


<?php

namespace DarkOak\Http\Controllers\Api\Application\Emails;

use DarkOak\Facades\Activity;
use DarkOak\Models\EmailTheme;
use DarkOak\Models\EmailTemplate;
use DarkOak\Models\User;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\Emails\DeleteEmailTemplateRequest;
use DarkOak\Http\Requests\Api\Application\Emails\GetEmailTemplatesRequest;
use DarkOak\Http\Requests\Api\Application\Emails\PreviewEmailTemplateRequest;
use DarkOak\Http\Requests\Api\Application\Emails\SendTestEmailTemplateRequest;
use DarkOak\Http\Requests\Api\Application\Emails\StoreEmailTemplateRequest;
use DarkOak\Http\Requests\Api\Application\Emails\UpdateEmailTemplateRequest;
use DarkOak\Transformers\Api\Application\Emails\EmailThemeTransformer;
use DarkOak\Transformers\Api\Application\Emails\EmailTemplateTransformer;
use DarkOak\Services\Emails\AnonymousRecipient;
use DarkOak\Services\Emails\EmailDispatchService;
use DarkOak\Services\Emails\EmailTemplateRenderer;
use DarkOak\Exceptions\Http\QueryValueOutOfRangeHttpException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class TemplateController extends ApplicationApiController
{
    public function __construct(
        private EmailTemplateRenderer $renderer,
        private EmailDispatchService $dispatch,
    ) {
        parent::__construct();
    }

    public function test(SendTestEmailTemplateRequest $request, EmailTemplate $template): Response
    {
        $user = null;
        if ($request->filled('user_id')) {
            $user = User::query()->findOrFail($request->integer('user_id'));
        }

        $email = $request->input('email');

        $recipient = $user;
        if ($user && $email && $email !== $user->email) {
            $recipient = $user->replicate();
            $recipient->email = $email;
        }

        if (!$recipient) {
            $recipient = $email;
        }

        $this->dispatch->send($template, [$recipient], $request->input('data', []));

        Activity::event('admin:emails:templates:test')
            ->property('template', $template)
            ->property('recipient', $email ?? $user?->email)
            ->description('A test email was sent for a template')
            ->log();

        return $this->returnNoContent();
    }

    public function index(GetEmailTemplatesRequest $request): array
    {
        $perPage = (int) $request->query('per_page', '50');
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $templates = QueryBuilder::for(EmailTemplate::query()->with('theme'))
            ->allowedFilters([
                'key',
                'name',
                AllowedFilter::exact('is_enabled'),
                AllowedFilter::exact('locale'),
            ])
            ->allowedIncludes(['theme'])
            ->allowedSorts(['name', 'key', 'locale', 'created_at', 'updated_at'])
            ->paginate($perPage);

        return $this->fractal->collection($templates)
            ->transformWith(EmailTemplateTransformer::class)
            ->toArray();
    }

    public function store(StoreEmailTemplateRequest $request): JsonResponse
    {
        $template = EmailTemplate::query()->create($this->buildTemplatePayload($request));

        Activity::event('admin:emails:templates:create')
            ->property('template', $template)
            ->description('An email template was created')
            ->log();

        return $this->fractal->item($template)
            ->transformWith(EmailTemplateTransformer::class)
            ->respond(Response::HTTP_CREATED);
    }

    public function view(GetEmailTemplatesRequest $request, EmailTemplate $template): array
    {
        $template->load('theme');

        return $this->fractal->item($template)
            ->transformWith(EmailTemplateTransformer::class)
            ->toArray();
    }

    public function update(UpdateEmailTemplateRequest $request, EmailTemplate $template): array
    {
        $payload = $this->buildTemplatePayload($request, $template);

        if (!empty($payload)) {
            $template->updateOrFail($payload);
        }

        Activity::event('admin:emails:templates:update')
            ->property('template', $template)
            ->property('changes', $payload)
            ->description('An email template was updated')
            ->log();

        return $this->fractal->item($template->refresh('theme'))
            ->transformWith(EmailTemplateTransformer::class)
            ->toArray();
    }

    public function delete(DeleteEmailTemplateRequest $request, EmailTemplate $template): Response
    {
        $template->delete();

        Activity::event('admin:emails:templates:delete')
            ->property('template', $template)
            ->description('An email template was deleted')
            ->log();

        return $this->returnNoContent();
    }

    public function preview(PreviewEmailTemplateRequest $request): array
    {
        $template = new EmailTemplate([
            'name' => $request->input('name', 'Preview Template'),
            'subject' => $request->input('subject'),
            'content' => $request->input('content'),
            'locale' => $request->input('locale', 'en'),
            'metadata' => $request->input('metadata', []),
            'is_enabled' => true,
        ]);

        if ($themeUuid = $request->input('theme_uuid')) {
            $theme = EmailTheme::query()->where('uuid', $themeUuid)->first();
            if (!$theme) {
                throw ValidationException::withMessages([
                    'theme_uuid' => 'The selected theme could not be found.',
                ]);
            }

            $template->setRelation('theme', $theme);
        }

    $rendered = $this->renderer->render($template, $this->preparePreviewContext($request->input('data', [])));

        $themeTransformer = new EmailThemeTransformer();

        return [
            'subject' => $rendered['subject'],
            'html' => $rendered['html'],
            'text' => $rendered['text'],
            'theme' => $themeTransformer->transform($rendered['theme']),
        ];
    }

    private function preparePreviewContext(array $data): array
    {
        $context = $data;

        $context['user'] = $this->resolvePreviewUser($context['user'] ?? null, $context);

        $resolvedCouponCode = $context['couponCode'] ?? $context['code'] ?? null;
        if (!$resolvedCouponCode) {
            $context['couponCode'] = 'PREVIEW-CODE';
            $resolvedCouponCode = $context['couponCode'];
        }

        if ($resolvedCouponCode && !isset($context['coupon'])) {
            $context['coupon'] = (object) [
                'code' => $resolvedCouponCode,
                'expires_at' => Carbon::now()->addDays(14),
            ];
        }

        return $context;
    }

    private function resolvePreviewUser(mixed $value, array $context): User|AnonymousRecipient
    {
        if ($value instanceof User || $value instanceof AnonymousRecipient) {
            return $value;
        }

        if ($value === null) {
            return $this->makeAnonymousRecipient(
                Arr::get($context, 'email'),
                Arr::get($context, 'username')
            );
        }

        if (is_string($value)) {
            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return AnonymousRecipient::fromEmail($value);
            }

            return $this->makeAnonymousRecipient(null, $value);
        }

    $payload = (array) $value;

        $email = Arr::get($payload, 'email', Arr::get($context, 'email'));
        $username = Arr::get($payload, 'username', Arr::get($payload, 'name'));
        $mode = Arr::get($payload, 'appearance_mode', Arr::get($payload, 'appearance.mode'));
        $lastMode = Arr::get(
            $payload,
            'appearance_last_mode',
            Arr::get($payload, 'appearance.last_mode', Arr::get($payload, 'appearance.lastMode'))
        );

        if ($username === null && $email) {
            $username = strstr($email, '@', true) ?: $email;
        }

        return new AnonymousRecipient(
            $this->resolvePreviewEmail($email),
            $username ?: 'Preview User',
            $mode ?: 'system',
            $lastMode ?: null
        );
    }

    private function makeAnonymousRecipient(?string $email, ?string $username): AnonymousRecipient
    {
        $resolvedEmail = $this->resolvePreviewEmail($email);
        $resolvedUsername = $username ?: (strstr($resolvedEmail, '@', true) ?: $resolvedEmail);

        return new AnonymousRecipient($resolvedEmail, $resolvedUsername, 'system', 'dark');
    }

    private function resolvePreviewEmail(?string $email): string
    {
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        return 'preview@example.com';
    }

    private function buildTemplatePayload(StoreEmailTemplateRequest|UpdateEmailTemplateRequest $request, ?EmailTemplate $existing = null): array
    {
        $fields = [
            'key',
            'name',
            'subject',
            'description',
            'content',
            'locale',
        ];

        $payload = [];
        foreach ($fields as $field) {
            if (!$existing || $request->exists($field)) {
                $payload[$field] = $request->input($field);
            }
        }

        if (!$existing) {
            $payload['is_enabled'] = $request->has('is_enabled') ? $request->boolean('is_enabled') : true;
        } elseif ($request->exists('is_enabled')) {
            $payload['is_enabled'] = $request->boolean('is_enabled');
        }

        if ($request->exists('metadata')) {
            $metadata = $request->input('metadata', []);

            if (!is_array($metadata)) {
                throw ValidationException::withMessages([
                    'metadata' => 'Metadata must be an array.',
                ]);
            }

            $payload['metadata'] = $metadata ?: [];
        }

        if ($request->exists('theme_uuid')) {
            $themeUuid = $request->input('theme_uuid');
            $theme = $themeUuid ? EmailTheme::query()->where('uuid', $themeUuid)->firstOrFail() : null;
            $payload['theme_id'] = $theme?->id;
        }

        return $payload;
    }
}


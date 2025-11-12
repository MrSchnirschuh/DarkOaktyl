<?php

namespace Everest\Http\Controllers\Api\Application\Emails;

use Everest\Facades\Activity;
use Everest\Models\EmailTheme;
use Everest\Models\EmailTemplate;
use Everest\Models\User;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Everest\Http\Controllers\Api\Application\ApplicationApiController;
use Everest\Http\Requests\Api\Application\Emails\DeleteEmailTemplateRequest;
use Everest\Http\Requests\Api\Application\Emails\GetEmailTemplatesRequest;
use Everest\Http\Requests\Api\Application\Emails\PreviewEmailTemplateRequest;
use Everest\Http\Requests\Api\Application\Emails\SendTestEmailTemplateRequest;
use Everest\Http\Requests\Api\Application\Emails\StoreEmailTemplateRequest;
use Everest\Http\Requests\Api\Application\Emails\UpdateEmailTemplateRequest;
use Everest\Transformers\Api\Application\Emails\EmailThemeTransformer;
use Everest\Transformers\Api\Application\Emails\EmailTemplateTransformer;
use Everest\Services\Emails\EmailDispatchService;
use Everest\Services\Emails\EmailTemplateRenderer;
use Everest\Exceptions\Http\QueryValueOutOfRangeHttpException;
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

        $rendered = $this->renderer->render($template, $request->input('data', []));

        $themeTransformer = new EmailThemeTransformer();

        return [
            'subject' => $rendered['subject'],
            'html' => $rendered['html'],
            'text' => $rendered['text'],
            'theme' => $themeTransformer->transform($rendered['theme']),
        ];
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

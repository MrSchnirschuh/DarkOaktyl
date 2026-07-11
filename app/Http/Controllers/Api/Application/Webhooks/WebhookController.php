<?php

namespace DarkOak\Http\Controllers\Api\Application\Webhooks;

use Illuminate\Http\Request;
use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use DarkOak\Models\Webhook;
use DarkOak\Models\WebhookLog;
use Illuminate\Support\Facades\Hash;
use Spatie\QueryBuilder\QueryBuilder;
use DarkOak\Services\Webhooks\WebhookDispatcher;
use DarkOak\Http\Requests\Api\Application\Webhooks\StoreWebhookRequest;
use DarkOak\Http\Requests\Api\Application\Webhooks\UpdateWebhookRequest;
use DarkOak\Transformers\Api\Application\WebhookTransformer;
use DarkOak\Transformers\Api\Application\WebhookLogTransformer;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;

class WebhookController extends ApplicationApiController
{
    /**
     * WebhookController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all configured webhooks.
     */
    public function index(Request $request): array
    {
        $webhooks = QueryBuilder::for(Webhook::query())
            ->allowedFilters(['name', 'url', 'enabled'])
            ->allowedSorts(['created_at', 'updated_at', 'last_sent_at'])
            ->withCount(['logs as successful_count' => function ($query) {
                $query->where('success', true);
            }])
            ->withCount(['logs as failed_count' => function ($query) {
                $query->where('success', false);
            }])
            ->paginate($request->input('per_page', 50));

        return $this->fractal->collection($webhooks)
            ->transformWith(WebhookTransformer::class)
            ->toArray();
    }

    /**
     * Get a single webhook by ID.
     */
    public function view(Request $request, Webhook $webhook): array
    {
        $webhook->loadCount([
            'logs as successful_count' => function ($query) {
                $query->where('success', true);
            },
            'logs as failed_count' => function ($query) {
                $query->where('success', false);
            },
        ]);

        return $this->fractal->item($webhook)
            ->transformWith(WebhookTransformer::class)
            ->toArray();
    }

    /**
     * Create a new webhook.
     */
    public function store(StoreWebhookRequest $request): Response
    {
        $webhook = Webhook::create($request->validated());

        Activity::event('admin:webhooks:create')
            ->subject($webhook)
            ->property('name', $webhook->name)
            ->description('Created a new webhook: ' . $webhook->name)
            ->log();

        return $this->returnCreatedResponse($webhook);
    }

    /**
     * Update a webhook.
     */
    public function update(UpdateWebhookRequest $request, Webhook $webhook): Response
    {
        $original = $webhook->toArray();

        $webhook->update($request->validated());

        Activity::event('admin:webhooks:update')
            ->subject($webhook)
            ->property('name', $webhook->name)
            ->property('old', $original)
            ->property('new', $webhook->toArray())
            ->description('Updated webhook configuration: ' . $webhook->name)
            ->log();

        return $this->returnNoContent();
    }

    /**
     * Delete a webhook.
     */
    public function delete(Request $request, Webhook $webhook): Response
    {
        Activity::event('admin:webhooks:delete')
            ->subject($webhook)
            ->property('name', $webhook->name)
            ->description('Deleted webhook: ' . $webhook->name)
            ->log();

        $webhook->delete();

        return $this->returnNoContent();
    }

    /**
     * Get logs for a webhook.
     */
    public function logs(Request $request, Webhook $webhook): array
    {
        $logs = QueryBuilder::for($webhook->logs())
            ->allowedFilters(['event', 'success'])
            ->allowedSorts(['created_at', 'attempt'])
            ->paginate($request->input('per_page', 25));

        return $this->fractal->collection($logs)
            ->transformWith(WebhookLogTransformer::class)
            ->toArray();
    }

    /**
     * Send a test event through the webhook.
     */
    public function test(Request $request, Webhook $webhook): JsonResponse|Response
    {
        $dispatcher = app(WebhookDispatcher::class);
        $success = $dispatcher->test($webhook);

        if ($success) {
            Activity::event('admin:webhooks:test')
                ->subject($webhook)
                ->property('name', $webhook->name)
                ->description('Test webhook sent successfully: ' . $webhook->name)
                ->log();

            return $this->returnNoContent();
        }

        return response()->json([
            'error' => 'Failed to send test webhook. Check logs for details.',
        ], 422);
    }

    /**
     * Regenerate webhook secret.
     */
    public function regenerateSecret(Request $request, Webhook $webhook): array
    {
        $secret = 'whsec_' . bin2hex(random_bytes(32));
        $webhook->update(['secret' => $secret]);

        Activity::event('admin:webhooks:regenerate')
            ->subject($webhook)
            ->property('name', $webhook->name)
            ->description('Regenerated webhook secret: ' . $webhook->name)
            ->log();

        return $this->fractal->item($webhook)
            ->transformWith(WebhookTransformer::class)
            ->toArray();
    }

    /**
     * Retry a failed webhook delivery.
     */
    public function retry(Request $request, WebhookLog $log): JsonResponse|Response
    {
        if ($log->success) {
            return response()->json([
                'error' => 'Cannot retry a successful delivery.',
            ], 422);
        }

        $dispatcher = app(WebhookDispatcher::class);
        $success = $dispatcher->resend($log);

        if ($success) {
            return $this->returnNoContent();
        }

        return response()->json([
            'error' => 'Failed to resend webhook. Check logs for details.',
        ], 422);
    }

    /**
     * Return HTTP/201 with location header.
     */
    protected function returnCreatedResponse(Webhook $webhook): JsonResponse|Response
    {
        return response()
            ->json($this->fractal->item($webhook)
            ->transformWith(WebhookTransformer::class)
            ->toArray(), 201)
            ->header('Location', route('application.webhooks.view', ['webhook' => $webhook->uuid]));
    }
}
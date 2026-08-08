<?php

namespace DarkOak\Http\Controllers\Api\Application\Webhooks;

use DarkOak\Models\Setting;
use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
use DarkOak\Models\WebhookEvent;
use Spatie\QueryBuilder\QueryBuilder;
use DarkOak\Http\Requests\Api\Application\Webhooks;
use DarkOak\Transformers\Api\Application\WebhookEventTransformer;
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
     * Get all available webhook events on the Panel.
     */
    public function index(Webhooks\GetWebhookEventsRequest $request): array
    {
        $events = QueryBuilder::for(WebhookEvent::query())
            ->allowedFilters(...['key'])
            ->get();

        return $this->transform($events, WebhookEventTransformer::class);
    }

    /**
     * Toggle whether a WebhookEvent is enabled.
     */
    public function toggle(Webhooks\ToggleWebhookEventRequest $request): Response
    {
        if ($request->input('id')) {
            $event = WebhookEvent::findOrFail($request->input('id'));

            $event->update(['enabled' => $request->input('enabled')]);
        } else {
            $events = WebhookEvent::all();

            foreach ($events as $event) {
                $event->update(['enabled' => $request->input('enabled')]);
            }
        }

        Activity::event('admin:webhooks:toggle')
            ->property('id', $request->input('id'))
            ->property('enabled', $request->input('enabled'))
            ->description('Webhook event(s) were toggled')
            ->log();

        return $this->returnNoContent();
    }

    /**
     * Send a basic test message through the webhook URL.
     */
    public function test(Webhooks\TestWebhookURIRequest $request): Response
    {
        Activity::event('admin:webhooks:test')
            ->description('The webhook integration was tested')
            ->log();

        return $this->returnNoContent();
    }

    /**
     * Update the webhook settings for the Panel.
     *
     * @throws \Throwable
     */
    public function settings(Webhooks\UpdateWebhookSettingsRequest $request): Response
    {
        Setting::set('settings::modules:webhooks:' . $request->input('key'), $request->input('value'));

        Activity::event('admin:webhooks:update')
            ->property('settings', $request->all())
            ->description('Jexpanelwebhook settings were updated')
            ->log();

        return $this->returnNoContent();
    }
}

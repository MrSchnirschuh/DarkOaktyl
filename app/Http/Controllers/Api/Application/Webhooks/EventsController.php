<?php

namespace Everest\Http\Controllers\Api\Application\Webhooks;

use Illuminate\Http\Request;
use Everest\Facades\Activity;
use Illuminate\Http\Response;
use Everest\Models\WebhookEvent;
use Spatie\QueryBuilder\QueryBuilder;
use Everest\Transformers\Api\Application\WebhookEventTransformer;
use Everest\Http\Controllers\Api\Application\ApplicationApiController;

class EventsController extends ApplicationApiController
{
    /**
     * EventsController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all available webhook events on the Panel.
     */
    public function index(Request $request): array
    {
        $events = QueryBuilder::for(WebhookEvent::query())
            ->allowedFilters(['key'])
            ->get();

        return $this->fractal->collection($events)
            ->transformWith(WebhookEventTransformer::class)
            ->toArray();
    }

    /**
     * Toggle whether a WebhookEvent is enabled.
     */
    public function toggle(Request $request): Response
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

        return $this->returnNoContent();
    }

    /**
     * Send a basic test message through the webhook URL.
     */
    public function test(Request $request): Response
    {
        Activity::event('admin:webhooks:test')
            ->description('The webhook integration was tested')
            ->log();

        return $this->returnNoContent();
    }
}

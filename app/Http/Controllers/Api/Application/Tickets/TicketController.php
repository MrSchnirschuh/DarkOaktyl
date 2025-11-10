<?php

namespace Everest\Http\Controllers\Api\Application\Tickets;

use Everest\Models\Ticket;
use Illuminate\Http\Request;
use Everest\Facades\Activity;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Everest\Transformers\Api\Application\TicketTransformer;
use Everest\Contracts\Repository\SettingsRepositoryInterface;
use Everest\Exceptions\Http\QueryValueOutOfRangeHttpException;
use Everest\Http\Controllers\Api\Application\ApplicationApiController;

class TicketController extends ApplicationApiController
{
    /**
     * TicketController constructor.
     */
    public function __construct(
        private SettingsRepositoryInterface $settings
    ) {
        parent::__construct();
    }

    /**
     * Return all the tickets urrently registered on the Panel.
     */
    public function index(Request $request): array
    {
        $perPage = (int) $request->query('per_page', '20');
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $tickets = QueryBuilder::for(Ticket::query())
            ->allowedFilters(['id', 'title', 'status', 'created_at'])
            ->allowedSorts(['id', 'title', 'status', 'created_at'])
            ->paginate($perPage);

        return $this->fractal->collection($tickets)
            ->transformWith(TicketTransformer::class)
            ->toArray();
    }

    /**
     * Add a new ticket to the Panel.
     */
    public function store(Request $request): JsonResponse
    {
        $ticket = Ticket::create([
            'title' => $request['title'],
            'user_id' => $request['user_id'],
            'assigned_to' => $request['assigned_to'] ?? null,
            'status' => $request['status'] ?? Ticket::STATUS_PENDING,
        ]);

        Activity::event('admin:tickets:create')
            ->property('ticket', $ticket)
            ->description('A ticket was created')
            ->log();

        return $this->fractal->item($ticket)
            ->transformWith(TicketTransformer::class)
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * View an existing ticket.
     */
    public function view(Request $request, Ticket $ticket): array
    {
        return $this->fractal->item($ticket)
            ->transformWith(TicketTransformer::class)
            ->toArray();
    }

    /**
     * Update an existing ticket.
     */
    public function update(Request $request, Ticket $ticket)
    {
        $ticket->update($request->all());

        Activity::event('admin:tickets:update')
            ->property('ticket', $ticket)
            ->property('new_data', $request->all())
            ->description('A ticket was updated')
            ->log();

        return $this->fractal->item($ticket)
            ->transformWith(TicketTransformer::class)
            ->toArray();
    }

    /**
     * Update the ticket settings for the Panel.
     *
     * @throws \Throwable
     */
    public function settings(Request $request): Response
    {
        $this->settings->set('settings::modules:tickets:' . $request->input('key'), $request->input('value'));

        Activity::event('admin:tickets:settings')
            ->property('settings', $request->all())
            ->description('Settings for the ticket module were updated')
            ->log();

        return $this->returnNoContent();
    }

    /**
     * Delete a ticket.
     */
    public function delete(Ticket $ticket, Request $request): Response
    {
        $ticket->messages()->delete();

        $ticket->delete();

        Activity::event('admin:tickets:delete')
            ->property('ticket', $ticket)
            ->description('A ticket was deleted')
            ->log();

        return $this->returnNoContent();
    }
}

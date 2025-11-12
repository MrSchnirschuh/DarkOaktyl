<?php

namespace DarkOak\Http\Controllers\Api\Application\Tickets;

use DarkOak\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DarkOak\Models\TicketMessage;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use DarkOak\Exceptions\Http\QueryValueOutOfRangeHttpException;
use DarkOak\Transformers\Api\Application\TicketMessageTransformer;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;

class TicketMessageController extends ApplicationApiController
{
    /**
     * TicketMessageController constructor.
     */
    public function __construct(
    ) {
        parent::__construct();
    }

    /**
     * Return all the ticket messages currently registered on the Panel.
     */
    public function index(Ticket $ticket, Request $request): array
    {
        $perPage = (int) $request->query('per_page', '20');
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $messages = QueryBuilder::for(TicketMessage::query())
            ->allowedFilters(['id'])
            ->allowedSorts(['id'])
            ->where('ticket_id', $ticket->id)
            ->paginate($perPage);

        return $this->fractal->collection($messages)
            ->transformWith(TicketMessageTransformer::class)
            ->toArray();
    }

    /**
     * Add a new message to a ticket.
     */
    public function store(Ticket $ticket, Request $request): JsonResponse
    {
        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->input('message'),
        ]);

        return $this->fractal->item($message)
            ->transformWith(TicketMessageTransformer::class)
            ->respond(Response::HTTP_CREATED);
    }
}


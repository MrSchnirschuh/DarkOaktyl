<?php

namespace DarkOak\Transformers\Api\Application;

use DarkOak\Models\TicketHistory;
use DarkOak\Transformers\Api\Transformer;

class TicketHistoryTransformer extends Transformer
{
    /**
     * List of resources that can be included.
     */
    protected array $availableIncludes = ['user', 'ticket'];

    public function getResourceName(): string
    {
        return TicketHistory::RESOURCE_NAME;
    }

    /**
     * Transform this model into a representation that can be consumed by a client.
     */
    public function transform(TicketHistory $model): array
    {
        return [
            'id' => $model->id,
            'ticket_id' => $model->ticket_id,
            'action' => $model->action,
            'action_description' => $model->getActionDescription(),
            'old_value' => $model->getOldValueFormatted(),
            'new_value' => $model->getNewValueFormatted(),
            'raw_old_value' => $model->old_value,
            'raw_new_value' => $model->new_value,
            'message' => $model->message,
            'user' => $model->user ? [
                'id' => $model->user->id,
                'username' => $model->user->username,
                'email' => $model->user->email,
                'name' => $model->user->name_first . ' ' . $model->user->name_last,
            ] : null,
            'created_at' => $model->created_at->toIso8601String(),
        ];
    }

    /**
     * Return the user who performed this action.
     */
    public function includeUser(TicketHistory $history): \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
    {
        if (!$history->user) {
            return $this->null();
        }
        return $this->item($history->user, new UserTransformer());
    }

    /**
     * Return the ticket associated with this history entry.
     */
    public function includeTicket(TicketHistory $history): \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
    {
        if (!$history->ticket) {
            return $this->null();
        }
        return $this->item($history->ticket, new TicketTransformer());
    }
}

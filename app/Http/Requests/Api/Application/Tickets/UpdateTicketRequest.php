<?php

namespace DarkOak\Http\Requests\Api\Application\Tickets;

use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateTicketRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|min:3|max:191',
            'status' => 'sometimes|string|in:open,in_progress,resolved,closed',
            'category' => 'sometimes|string|in:technical,billing,general',
            'priority' => 'sometimes|string|in:low,medium,high,critical',
            'assigned_to' => 'nullable|int|exists:users,id',
            'status_message' => 'nullable|string|max:500',
        ];
    }

    public function permission(): ?string
    {
        return 'tickets.update';
    }
}

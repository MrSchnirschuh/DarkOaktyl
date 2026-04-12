<?php

namespace DarkOak\Http\Requests\Api\Application\Tickets;

use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreTicketRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:3|max:191',
            'user_id' => 'required|int|exists:users,id',
            'category' => 'nullable|string|in:technical,billing,general',
            'priority' => 'nullable|string|in:low,medium,high,critical',
            'assigned_to' => 'nullable|int|exists:users,id',
        ];
    }

    public function permission(): ?string
    {
        return 'tickets.create';
    }
}

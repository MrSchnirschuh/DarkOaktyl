<?php

namespace DarkOak\Http\Requests\Api\Application\Webhooks;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreWebhookRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:191',
            'url' => 'required|url|max:500',
            'secret' => 'nullable|string|max:255',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:server.created,server.deleted,user.registered,billing.order.completed',
            'enabled' => 'sometimes|boolean',
        ];
    }

    public function permission(): string
    {
        return AdminRole::WEBHOOKS_CREATE;
    }
}
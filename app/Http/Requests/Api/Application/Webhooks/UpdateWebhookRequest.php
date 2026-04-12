<?php

namespace DarkOak\Http\Requests\Api\Application\Webhooks;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateWebhookRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:191',
            'url' => 'sometimes|url|max:500',
            'secret' => 'nullable|string|max:255',
            'events' => 'sometimes|array|min:1',
            'events.*' => 'string|in:server.created,server.deleted,user.registered,billing.order.completed',
            'enabled' => 'sometimes|boolean',
        ];
    }

    public function permission(): string
    {
        return AdminRole::WEBHOOKS_UPDATE;
    }
}
<?php

namespace DarkOak\Http\Requests\Api\Application\Webhooks;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class ToggleWebhookEventRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'id' => 'nullable|int|exists:webhook_events,id',
            'enabled' => 'required|bool',
        ];
    }

    public function permission(): string
    {
        return AdminRole::WEBHOOKS_UPDATE;
    }
}

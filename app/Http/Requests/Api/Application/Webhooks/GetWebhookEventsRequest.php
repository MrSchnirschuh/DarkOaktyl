<?php

namespace DarkOak\Http\Requests\Api\Application\Webhooks;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class GetWebhookEventsRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::WEBHOOKS_READ;
    }
}

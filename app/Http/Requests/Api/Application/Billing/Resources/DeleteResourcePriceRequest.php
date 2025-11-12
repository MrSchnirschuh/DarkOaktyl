<?php

namespace DarkOak\Http\Requests\Api\Application\Billing\Resources;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteResourcePriceRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_RESOURCES_DELETE;
    }
}


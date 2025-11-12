<?php

namespace DarkOak\Http\Requests\Api\Application\Billing\Orders;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class GetBillingOrdersRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_ORDERS;
    }
}


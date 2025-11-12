<?php

namespace DarkOak\Http\Requests\Api\Application\Billing\Exceptions;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class GetBillingExceptionsRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_EXCEPTIONS;
    }
}


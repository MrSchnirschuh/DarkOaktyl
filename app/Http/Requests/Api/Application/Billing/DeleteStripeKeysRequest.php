<?php

namespace DarkOak\Http\Requests\Api\Application\Billing;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteStripeKeysRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_DELETE_KEYS;
    }
}


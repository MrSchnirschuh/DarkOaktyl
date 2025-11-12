<?php

namespace DarkOak\Http\Requests\Api\Application\Billing\Terms;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteBillingTermRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_TERMS_DELETE;
    }
}


<?php

namespace DarkOak\Http\Requests\Api\Application\Billing\Invoices;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class GetBillingInvoicesRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_INVOICES;
    }
}

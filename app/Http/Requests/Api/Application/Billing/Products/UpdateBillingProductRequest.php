<?php

namespace DarkOak\Http\Requests\Api\Application\Billing\Products;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateBillingProductRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_PRODUCTS_UPDATE;
    }
}


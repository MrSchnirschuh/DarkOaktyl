<?php

namespace DarkOak\Http\Requests\Api\Application\Billing\Categories;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreBillingCategoryRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_CATEGORIES_CREATE;
    }
}


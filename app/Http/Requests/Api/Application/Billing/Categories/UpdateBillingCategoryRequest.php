<?php

namespace DarkOak\Http\Requests\Api\Application\Billing\Categories;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateBillingCategoryRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_CATEGORIES_UPDATE;
    }
}


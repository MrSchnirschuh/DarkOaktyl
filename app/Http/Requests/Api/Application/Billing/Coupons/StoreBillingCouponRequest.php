<?php

namespace Everest\Http\Requests\Api\Application\Billing\Coupons;

use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreBillingCouponRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_PRODUCTS_CREATE;
    }
}

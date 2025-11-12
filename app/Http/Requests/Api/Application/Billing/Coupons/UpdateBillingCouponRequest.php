<?php

namespace Everest\Http\Requests\Api\Application\Billing\Coupons;

use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateBillingCouponRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_PRODUCTS_WRITE;
    }
}

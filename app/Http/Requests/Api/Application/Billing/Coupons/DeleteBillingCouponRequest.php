<?php

namespace DarkOak\Http\Requests\Api\Application\Billing\Coupons;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteBillingCouponRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_COUPONS_DELETE;
    }
}


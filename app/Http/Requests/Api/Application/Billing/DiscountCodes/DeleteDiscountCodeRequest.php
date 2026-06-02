<?php

namespace DarkOak\Http\Requests\Api\Application\Billing\DiscountCodes;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteDiscountCodeRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_DISCOUNT_CODES;
    }
}

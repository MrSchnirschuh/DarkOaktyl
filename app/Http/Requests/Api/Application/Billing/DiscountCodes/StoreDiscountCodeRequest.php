<?php

namespace DarkOak\Http\Requests\Api\Application\Billing\DiscountCodes;

use DarkOak\Models\AdminRole;
use DarkOak\Models\Billing\DiscountCode;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreDiscountCodeRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return DiscountCode::$validationRules;
    }

    public function permission(): string
    {
        return AdminRole::BILLING_DISCOUNT_CODES;
    }
}

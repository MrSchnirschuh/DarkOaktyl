<?php

namespace Everest\Http\Requests\Api\Application\Billing\DiscountCodes;

use Everest\Models\AdminRole;
use Everest\Models\Billing\DiscountCode;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

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

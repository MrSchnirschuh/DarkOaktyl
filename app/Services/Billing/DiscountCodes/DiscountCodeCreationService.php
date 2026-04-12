<?php

namespace Everest\Services\Billing\DiscountCodes;

use Everest\Models\Billing\DiscountCode;

class DiscountCodeCreationService
{
    /**
     * Process the creation of a discount code and return it.
     */
    public function handle(array $data): DiscountCode
    {
        $discount_code = new DiscountCode();

        $data['expires_at'] = $data['expires_at'] ?? null;

        $discount_code->fill($data);

        $discount_code->saveOrFail();

        return $discount_code;
    }
}

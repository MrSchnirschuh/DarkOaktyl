<?php

namespace DarkOak\Services\Billing\DiscountCodes;

use DarkOak\Models\Billing\DiscountCode;

class DiscountCodeUpdateService
{
    /**
     * Process the update of a discount code and return it.
     */
    public function handle(DiscountCode $discount_code, array $data): DiscountCode
    {
        $data['expires_at'] = $data['expires_at'] ?? null;

        $discount_code->fill($data);

        $discount_code->saveOrFail();

        return $discount_code;
    }
}

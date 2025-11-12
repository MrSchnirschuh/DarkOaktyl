<?php

namespace Everest\Http\Requests\Api\Application\Billing\Coupons;

use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class SendBillingCouponRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_COUPONS_UPDATE;
    }

    public function rules(): array
    {
        return [
            'template_uuid' => ['required', 'string', 'exists:email_templates,uuid'],
            'user_ids' => ['sometimes', 'array', 'required_without:emails'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'emails' => ['sometimes', 'array', 'required_without:user_ids'],
            'emails.*' => ['email'],
            'context' => ['nullable', 'array'],
        ];
    }
}

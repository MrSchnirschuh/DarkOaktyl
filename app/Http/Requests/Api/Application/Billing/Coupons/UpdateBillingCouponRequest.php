<?php

namespace DarkOak\Http\Requests\Api\Application\Billing\Coupons;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use DarkOak\Models\Billing\Coupon;

class UpdateBillingCouponRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_COUPONS_UPDATE;
    }

    public function rules(): array
    {
        /** @var Coupon|null $coupon */
        $coupon = $this->route('coupon');
        $couponId = $coupon?->id;

        return [
            'code' => ['sometimes', 'nullable', 'string', 'max:64', 'alpha_dash', Rule::unique('coupons', 'code')->ignore($couponId)],
            'name' => ['sometimes', 'string', 'max:191'],
            'description' => ['sometimes', 'nullable', 'string'],
            'type' => ['sometimes', 'nullable', 'string', Rule::in(['amount', 'percentage', 'custom'])],
            'value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'percentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:1'],
            'max_usages' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'per_user_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'applies_to_term_id' => ['sometimes', 'nullable', 'integer', 'exists:billing_terms,id'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator)
    {
        /** @var Coupon|null $coupon */
        $coupon = $this->route('coupon');

        $validator->after(function (Validator $validator) use ($coupon) {
            $currentType = $coupon?->type ?? 'amount';
            $type = $this->has('type') ? Str::lower((string) $this->input('type')) : $currentType;

            if (!in_array($type, ['amount', 'percentage', 'custom'], true)) {
                $validator->errors()->add('type', 'Invalid coupon type provided.');
                return;
            }

            $value = $this->has('value') ? $this->input('value') : ($coupon?->value);
            $percentage = $this->has('percentage') ? $this->input('percentage') : ($coupon?->percentage);

            if ($type === 'amount' && $value === null) {
                $validator->errors()->add('value', 'A monetary value is required when the coupon type is amount.');
            }

            if ($type === 'percentage' && $percentage === null) {
                $validator->errors()->add('percentage', 'A percentage is required when the coupon type is percentage.');
            }

            if (in_array($type, ['amount', 'percentage'], true) && $value === null && $percentage === null) {
                $validator->errors()->add('value', 'A value or percentage must be provided.');
            }
        });
    }

    protected function prepareForValidation()
    {
        if ($this->has('code')) {
            $code = $this->input('code');
            $this->merge(['code' => $code !== null ? Str::upper($code) : null]);
        }

        if ($this->has('type')) {
            $type = $this->input('type');
            $this->merge(['type' => $type !== null ? Str::lower($type) : null]);
        }

        parent::prepareForValidation();
    }
}


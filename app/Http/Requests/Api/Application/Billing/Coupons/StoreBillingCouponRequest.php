<?php

namespace Everest\Http\Requests\Api\Application\Billing\Coupons;

use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBillingCouponRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_COUPONS_CREATE;
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:64', 'alpha_dash', 'unique:coupons,code'],
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string', Rule::in(['amount', 'percentage', 'custom'])],
            'value' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'max_usages' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'applies_to_term_id' => ['nullable', 'integer', 'exists:billing_terms,id'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('type') ? Str::lower($this->input('type')) : 'amount';
            $value = $this->input('value');
            $percentage = $this->input('percentage');

            if (!in_array($type, ['amount', 'percentage', 'custom'], true)) {
                $validator->errors()->add('type', 'Invalid coupon type provided.');
                return;
            }

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
        if ($this->has('code') && $this->input('code') !== null) {
            $this->merge(['code' => Str::upper($this->input('code'))]);
        }

        if ($this->has('type') && $this->input('type') !== null) {
            $this->merge(['type' => Str::lower($this->input('type'))]);
        }

        parent::prepareForValidation();
    }
}

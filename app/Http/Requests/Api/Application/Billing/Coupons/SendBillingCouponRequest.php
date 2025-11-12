<?php

namespace Everest\Http\Requests\Api\Application\Billing\Coupons;

use Everest\Models\AdminRole;
use Illuminate\Validation\Validator;
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
            'personalize' => ['sometimes', 'boolean'],
            'personalized_code_prefix' => ['nullable', 'string', 'max:16'],
            'personalized_code_length' => ['nullable', 'integer', 'min:4', 'max:24'],
            'personalized_max_usages' => ['nullable', 'integer', 'min:1'],
            'personalized_per_user_limit' => ['nullable', 'integer', 'min:1'],
            'personalized_starts_at' => ['nullable', 'date'],
            'personalized_expires_at' => ['nullable', 'date'],
            'personalized_metadata' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $personalize = $this->boolean('personalize');

            if ($personalize) {
                $userIds = collect($this->input('user_ids', []))->filter();
                $emails = collect($this->input('emails', []))->filter();

                if ($userIds->isEmpty()) {
                    $validator->errors()->add('user_ids', 'Provide at least one user when personalizing coupons.');
                }

                if ($emails->isNotEmpty()) {
                    $validator->errors()->add('emails', 'Personalized coupons cannot be sent to raw email addresses.');
                }

                if ($this->filled('personalized_starts_at') && $this->filled('personalized_expires_at')) {
                    $startsAt = strtotime((string) $this->input('personalized_starts_at'));
                    $expiresAt = strtotime((string) $this->input('personalized_expires_at'));

                    if ($startsAt !== false && $expiresAt !== false && $expiresAt < $startsAt) {
                        $validator->errors()->add('personalized_expires_at', 'The personalized coupon expiration must be after the start date.');
                    }
                }
            }
        });
    }
}

<?php

namespace DarkOak\Http\Requests\Api\Client\Billing\Organizations;

use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class UpdateOrganizationRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return [
            'name' => 'string|max:255|nullable',
            'description' => 'string|nullable|max:1000',
            'monthly_budget' => 'numeric|nullable|min:0',
            'billing_address' => 'string|nullable|max:500',
            'tax_id' => 'string|nullable|max:100',
        ];
    }
}

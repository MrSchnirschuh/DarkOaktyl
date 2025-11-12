<?php

namespace DarkOak\Http\Requests\Api\Application\Billing\Resources;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;
use Illuminate\Validation\Rule;

class StoreResourcePriceRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_RESOURCES_CREATE;
    }

    public function rules(): array
    {
        return [
            'resource' => ['required', 'string', 'max:64', 'alpha_dash', 'lowercase', 'unique:billing_resource_prices,resource'],
            'display_name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'unit' => ['nullable', 'string', 'max:32'],
            'base_quantity' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'min_quantity' => ['nullable', 'integer', 'min:0'],
            'max_quantity' => ['nullable', 'integer', 'min:0'],
            'default_quantity' => ['nullable', 'integer', 'min:0'],
            'step_quantity' => ['nullable', 'integer', 'min:1'],
            'is_visible' => ['sometimes', 'boolean'],
            'is_metered' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
            'scaling_rules' => ['nullable', 'array'],
            'scaling_rules.*.threshold' => ['required_with:scaling_rules', 'integer', 'min:0'],
            'scaling_rules.*.multiplier' => ['required_with:scaling_rules', 'numeric'],
            'scaling_rules.*.mode' => ['nullable', 'string', Rule::in(['multiplier', 'surcharge'])],
            'scaling_rules.*.label' => ['nullable', 'string', 'max:191'],
            'scaling_rules.*.metadata' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('resource')) {
            $this->merge(['resource' => strtolower($this->input('resource'))]);
        }

        parent::prepareForValidation();
    }
}


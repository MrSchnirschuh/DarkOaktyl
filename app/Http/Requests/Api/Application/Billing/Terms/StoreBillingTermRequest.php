<?php

namespace Everest\Http\Requests\Api\Application\Billing\Terms;

use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreBillingTermRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_TERMS_CREATE;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['nullable', 'string', 'max:191', 'alpha_dash', 'lowercase', 'unique:billing_terms,slug'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'multiplier' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation()
    {
        $slug = $this->input('slug');
        $name = $this->input('name');

        if (empty($slug) && !empty($name)) {
            $slug = Str::slug($name);
        }

        if (!empty($slug)) {
            $this->merge(['slug' => Str::slug($slug)]);
        }

        parent::prepareForValidation();
    }
}

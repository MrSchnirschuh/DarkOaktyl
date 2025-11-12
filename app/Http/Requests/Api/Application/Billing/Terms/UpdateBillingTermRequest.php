<?php

namespace Everest\Http\Requests\Api\Application\Billing\Terms;

use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Everest\Models\Billing\BillingTerm;

class UpdateBillingTermRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_TERMS_UPDATE;
    }

    public function rules(): array
    {
        /** @var BillingTerm|null $term */
        $term = $this->route('term');
        $termId = $term?->id;

        return [
            'name' => ['sometimes', 'string', 'max:191'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:191', 'alpha_dash', 'lowercase', Rule::unique('billing_terms', 'slug')->ignore($termId)],
            'duration_days' => ['sometimes', 'integer', 'min:1'],
            'multiplier' => ['sometimes', 'numeric', 'min:0'],
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

        if ($this->has('slug') && $slug !== null) {
            $slug = Str::slug($slug);
        } elseif (!$this->has('slug') && $this->has('name')) {
            $slug = Str::slug($name);
        }

        if (isset($slug) && $slug !== '') {
            $this->merge(['slug' => $slug]);
        }

        parent::prepareForValidation();
    }
}

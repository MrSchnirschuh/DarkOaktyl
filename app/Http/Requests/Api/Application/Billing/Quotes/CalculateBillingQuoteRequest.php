<?php

namespace DarkOak\Http\Requests\Api\Application\Billing\Quotes;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CalculateBillingQuoteRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_READ;
    }

    public function rules(): array
    {
        return [
            'resources' => ['required', 'array', 'min:1'],
            'resources.*.resource' => ['required', 'string', 'max:64', Rule::exists('billing_resource_prices', 'resource')],
            'resources.*.quantity' => ['required', 'integer', 'min:0'],
            'term' => ['nullable', 'string', 'max:191', Rule::exists('billing_terms', 'uuid')],
            'coupons' => ['nullable', 'array'],
            'coupons.*' => ['string', 'max:64', Rule::exists('coupons', 'code')],
            'options' => ['nullable', 'array'],
            'options.snap_to_step' => ['nullable', 'boolean'],
            'options.validate_capacity' => ['nullable', 'boolean'],
            'options.node_uuid' => ['nullable', 'string', 'max:191', Rule::exists('nodes', 'uuid')],
            'options.node_id' => ['nullable', 'integer', Rule::exists('nodes', 'id')],
        ];
    }

    protected function prepareForValidation()
    {
        $resources = collect($this->input('resources', []))->map(function ($entry) {
            if (is_array($entry)) {
                $entry['resource'] = isset($entry['resource']) ? Str::lower($entry['resource']) : null;
                $entry['quantity'] = isset($entry['quantity']) ? (int) $entry['quantity'] : null;
            }

            return $entry;
        })->toArray();

        $coupons = collect($this->input('coupons', []))
            ->filter()
            ->map(fn ($code) => Str::upper((string) $code))
            ->values()
            ->toArray();

        $data = [
            'resources' => $resources,
            'coupons' => $coupons,
        ];

        if ($this->has('term') && $this->input('term') !== null) {
            $data['term'] = Str::lower((string) $this->input('term'));
        }

        $options = $this->input('options', []);
        if (is_array($options)) {
            if (array_key_exists('node_id', $options) && $options['node_id'] !== null) {
                $options['node_id'] = (int) $options['node_id'];
            }

            $data['options'] = $options;
        }

        $this->merge($data);

        parent::prepareForValidation();
    }
}


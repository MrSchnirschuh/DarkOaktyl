<?php

namespace DarkOak\Http\Requests\Api\Client\Billing\Builder;

use Illuminate\Validation\Rule;

class BuilderIntentRequest extends BuilderQuoteRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'node_id' => ['required', 'integer', Rule::exists('nodes', 'id')],
            'variables' => ['nullable', 'array'],
            'variables.*.key' => ['required', 'string', 'max:191'],
            'variables.*.value' => ['nullable', 'string', 'max:191'],
            'renewal' => ['nullable', 'boolean'],
            'server_id' => ['nullable', 'integer', Rule::exists('servers', 'id')],
        ]);
    }
}

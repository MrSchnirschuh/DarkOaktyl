<?php

namespace DarkOak\Http\Requests\Api\Client\Billing\Builder;

use DarkOak\Http\Requests\Api\Client\ClientApiRequest;
use Illuminate\Validation\Rule;

class BuilderIntentUpdateRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return [
            'intent' => ['required', 'string'],
            'node_id' => ['required', 'integer', Rule::exists('nodes', 'id')],
            'variables' => ['nullable', 'array'],
            'variables.*.key' => ['required', 'string', 'max:191'],
            'variables.*.value' => ['nullable', 'string', 'max:191'],
            'renewal' => ['nullable', 'boolean'],
            'server_id' => ['nullable', 'integer', Rule::exists('servers', 'id')],
        ];
    }
}

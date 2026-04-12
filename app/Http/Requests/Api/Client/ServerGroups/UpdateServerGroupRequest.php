<?php

namespace DarkOak\Http\Requests\Api\Client\ServerGroups;

use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class UpdateServerGroupRequest extends ClientApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|min:1|max:255',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'icon' => 'nullable|string|max:50',
        ];
    }
}
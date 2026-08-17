<?php

namespace DarkOak\Http\Requests\Api\Client\ServerGroups;

use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class StoreServerGroupRequest extends ClientApiRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via checkGroupLimit
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:1|max:255',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'icon' => 'nullable|string|max:50|alpha_dash',
        ];
    }
}

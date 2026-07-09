<?php

namespace DarkOak\Http\Requests\Api\Client\Organizations;

use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class CreateOrganizationRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'string|nullable|max:1000',
        ];
    }
}

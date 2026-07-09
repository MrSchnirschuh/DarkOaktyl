<?php

namespace DarkOak\Http\Requests\Api\Client\Organizations;

use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class UpdateOrganizationRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return [
            'name' => 'string|max:255|nullable',
            'description' => 'string|nullable|max:1000',
        ];
    }
}

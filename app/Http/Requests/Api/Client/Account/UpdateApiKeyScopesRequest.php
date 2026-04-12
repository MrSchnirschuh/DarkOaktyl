<?php

namespace DarkOak\Http\Requests\Api\Client\Account;

use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class UpdateApiKeyScopesRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return [
            'scopes' => 'required|array',
            'scopes.*' => 'string',
        ];
    }
}

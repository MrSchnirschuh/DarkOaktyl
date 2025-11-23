<?php

namespace DarkOak\Http\Requests\Api\Client\Account;

use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class StorePasskeyRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'token' => ['required', 'string'],
            'credential' => ['required', 'array'],
        ];
    }
}

<?php

namespace DarkOak\Http\Requests\Api\Client\Account;

use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class UpdatePasskeyMethodRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return [
            'method' => ['required', 'in:password,passkey,both'],
        ];
    }
}

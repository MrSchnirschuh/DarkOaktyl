<?php

namespace DarkOak\Http\Requests\Api\Client\Account;

use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class SetupUserRequest extends ClientApiRequest
{
    /**
     * @throws \DarkOak\Exceptions\Http\Base\InvalidPasswordProvidedException
     */
    public function authorize(): bool
    {
        if (!parent::authorize()) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}


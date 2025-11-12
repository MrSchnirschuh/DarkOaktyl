<?php

namespace Everest\Http\Requests\Api\Client\Account;

use Everest\Http\Requests\Api\Client\ClientApiRequest;

class UpdateAppearanceRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return [
            'mode' => 'required|string|in:system,light,dark',
            'last_mode' => 'required|string|in:light,dark',
        ];
    }
}

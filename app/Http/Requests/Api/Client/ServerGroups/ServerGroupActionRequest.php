<?php

namespace DarkOak\Http\Requests\Api\Client\ServerGroups;

use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class ServerGroupActionRequest extends ClientApiRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via authorizeGroup
    }

    public function rules(): array
    {
        return [
            'server_uuid' => 'required|string|max:36|exists:servers,uuid',
        ];
    }
}

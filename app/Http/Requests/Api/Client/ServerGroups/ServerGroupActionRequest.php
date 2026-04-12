<?php

namespace DarkOak\Http\Requests\Api\Client\ServerGroups;

use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class ServerGroupActionRequest extends ClientApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'server_uuid' => 'required|string|exists:servers,uuid',
        ];
    }
}
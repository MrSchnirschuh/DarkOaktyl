<?php

namespace DarkOak\Http\Requests\Api\Client\Servers\Files;

use DarkOak\Models\Permission;
use DarkOak\Contracts\Http\ClientPermissionsRequest;
use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class ChmodFilesRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permission::ACTION_FILE_UPDATE;
    }

    public function rules(): array
    {
        return [
            'root' => 'required|nullable|string',
            'files' => 'required|array',
            'files.*.file' => 'required|string',
            'files.*.mode' => 'required|numeric',
        ];
    }
}


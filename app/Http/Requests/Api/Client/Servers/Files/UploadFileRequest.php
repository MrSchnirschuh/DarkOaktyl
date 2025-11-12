<?php

namespace DarkOak\Http\Requests\Api\Client\Servers\Files;

use DarkOak\Models\Permission;
use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class UploadFileRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return Permission::ACTION_FILE_CREATE;
    }
}


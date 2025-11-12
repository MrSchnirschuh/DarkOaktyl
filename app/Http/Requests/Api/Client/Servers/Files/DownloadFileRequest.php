<?php

namespace DarkOak\Http\Requests\Api\Client\Servers\Files;

use DarkOak\Models\Server;
use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class DownloadFileRequest extends ClientApiRequest
{
    /**
     * Ensure that the user making this request has permission to download files
     * from this server.
     */
    public function authorize(): bool
    {
        return $this->user()->can('file.read', $this->route()->parameter('server'));
    }
}


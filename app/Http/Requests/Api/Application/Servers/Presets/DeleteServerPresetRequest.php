<?php

namespace DarkOak\Http\Requests\Api\Application\Servers\Presets;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteServerPresetRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::SERVER_PRESETS_DELETE;
    }
}

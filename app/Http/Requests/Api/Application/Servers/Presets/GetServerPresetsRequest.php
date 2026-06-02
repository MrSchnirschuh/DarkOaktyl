<?php

namespace DarkOak\Http\Requests\Api\Application\Servers\Presets;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class GetServerPresetsRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'search' => 'string|max:100',
        ];
    }

    public function permission(): string
    {
        return AdminRole::SERVER_PRESETS_READ;
    }
}

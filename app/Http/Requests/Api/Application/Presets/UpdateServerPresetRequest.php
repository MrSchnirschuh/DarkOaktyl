<?php

namespace DarkOak\Http\Requests\Api\Application\Presets;

use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;
use DarkOak\Models\ServerPreset;
use DarkOak\Models\AdminRole;

class UpdateServerPresetRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        $rules = ServerPreset::$validationRules;

        return [
            'name' => 'sometimes|required|string|max:191',
            'description' => 'nullable|string',
            'settings' => 'nullable|array',
            'port_start' => 'nullable|integer|min:1|max:65535',
            'port_end' => 'nullable|integer|min:1|max:65535|gte:port_start',
            'visibility' => 'sometimes|in:global,private',
            'naming' => 'nullable|array',
        ];
    }

    public function permission(): string
    {
        return AdminRole::SERVERS_UPDATE;
    }
}

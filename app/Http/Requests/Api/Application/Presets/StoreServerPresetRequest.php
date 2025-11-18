<?php

namespace DarkOak\Http\Requests\Api\Application\Presets;

use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;
use DarkOak\Models\ServerPreset;
use DarkOak\Models\AdminRole;

class StoreServerPresetRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        $rules = ServerPreset::$validationRules;

        return [
            'name' => $rules['name'],
            'description' => 'nullable|string',
            'settings' => 'nullable|array',
            'port_start' => $rules['port_start'],
            'port_end' => $rules['port_end'],
            'visibility' => 'required|in:global,private',
            'naming' => 'nullable|array',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        // Ensure json arrays are preserved
        return is_null($key) ? $data : ($data[$key] ?? $default);
    }

    public function permission(): string
    {
        return AdminRole::SERVERS_CREATE;
    }
}

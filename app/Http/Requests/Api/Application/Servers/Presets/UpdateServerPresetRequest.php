<?php

namespace DarkOak\Http\Requests\Api\Application\Servers\Presets;

use DarkOak\Models\AdminRole;
use DarkOak\Models\ServerPreset;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateServerPresetRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return ServerPreset::rules();
    }

    public function permission(): string
    {
        return AdminRole::SERVER_PRESETS_UPDATE;
    }
}

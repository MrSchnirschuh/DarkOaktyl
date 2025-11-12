<?php

namespace DarkOak\Http\Requests\Api\Application\Mounts;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class MountEggsRequest extends ApplicationApiRequest
{
    public function rules(array $rules = null): array
    {
        return $rules ?? ['eggs' => 'required|exists:eggs,id'];
    }

    public function permission(): string
    {
        return AdminRole::MOUNTS_UPDATE;
    }
}


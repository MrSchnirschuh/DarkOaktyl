<?php

namespace DarkOak\Http\Requests\Api\Application\Mounts;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class MountNodesRequest extends ApplicationApiRequest
{
    public function rules(array $rules = null): array
    {
        return $rules ?? ['nodes' => 'required|exists:nodes,id'];
    }

    public function permission(): string
    {
        return AdminRole::MOUNTS_UPDATE;
    }
}


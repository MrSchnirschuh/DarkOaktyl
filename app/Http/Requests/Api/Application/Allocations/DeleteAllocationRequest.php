<?php

namespace DarkOak\Http\Requests\Api\Application\Allocations;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteAllocationRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::NODES_UPDATE;
    }
}


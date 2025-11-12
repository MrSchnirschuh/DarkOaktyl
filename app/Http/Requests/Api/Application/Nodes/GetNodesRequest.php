<?php

namespace DarkOak\Http\Requests\Api\Application\Nodes;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class GetNodesRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::NODES_READ;
    }
}


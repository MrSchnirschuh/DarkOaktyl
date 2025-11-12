<?php

namespace DarkOak\Http\Requests\Api\Application\Nodes;

use DarkOak\Models\AdminRole;

class GetNodeRequest extends GetNodesRequest
{
    public function permission(): string
    {
        return AdminRole::NODES_READ;
    }
}


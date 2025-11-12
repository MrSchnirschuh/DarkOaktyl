<?php

namespace DarkOak\Http\Requests\Api\Application\Nodes;

use DarkOak\Models\Node;
use DarkOak\Models\AdminRole;

class UpdateNodeRequest extends StoreNodeRequest
{
    public function rules(array $rules = null): array
    {
        return parent::rules($rules ?? Node::getRulesForUpdate($this->route()->parameter('node')));
    }

    public function permission(): string
    {
        return AdminRole::NODES_UPDATE;
    }
}


<?php

namespace DarkOak\Http\Requests\Api\Application\Servers;

use DarkOak\Models\AdminRole;

class TransferServerRequest extends ServerWriteRequest
{
    public function permission(): string
    {
        return AdminRole::SERVERS_UPDATE;
    }

    public function rules(): array
    {
        return [
            'node_id' => 'required|integer|exists:nodes,id',
            'allocation_id' => 'required|integer|exists:allocations,id',
            'additional_allocations' => 'sometimes|array',
            'additional_allocations.*' => 'integer|exists:allocations,id',
        ];
    }
}

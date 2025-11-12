<?php

namespace DarkOak\Http\Requests\Api\Application\Mounts;

use DarkOak\Models\AdminRole;

class GetMountRequest extends GetMountsRequest
{
    public function permission(): string
    {
        return AdminRole::MOUNTS_READ;
    }
}


<?php

namespace DarkOak\Http\Requests\Api\Application\Nests;

use DarkOak\Models\AdminRole;

class GetNestRequest extends GetNestsRequest
{
    public function permission(): string
    {
        return AdminRole::NESTS_READ;
    }
}


<?php

namespace DarkOak\Http\Requests\Api\Application\Eggs;

use DarkOak\Models\AdminRole;

class GetEggRequest extends GetEggsRequest
{
    public function permission(): string
    {
        return AdminRole::EGGS_READ;
    }
}


<?php

namespace DarkOak\Http\Requests\Api\Application\Eggs;

use DarkOak\Models\Egg;
use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteEggRequest extends ApplicationApiRequest
{
    public function resourceExists(): bool
    {
        $egg = $this->route()->parameter('egg');

        return $egg instanceof Egg && $egg->exists;
    }

    public function permission(): string
    {
        return AdminRole::EGGS_DELETE;
    }
}


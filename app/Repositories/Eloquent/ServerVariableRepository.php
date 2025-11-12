<?php

namespace DarkOak\Repositories\Eloquent;

use DarkOak\Models\ServerVariable;
use DarkOak\Contracts\Repository\ServerVariableRepositoryInterface;

class ServerVariableRepository extends EloquentRepository implements ServerVariableRepositoryInterface
{
    /**
     * Return the model backing this repository.
     */
    public function model(): string
    {
        return ServerVariable::class;
    }
}


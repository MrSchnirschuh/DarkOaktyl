<?php

namespace DarkOak\Repositories\Eloquent;

use DarkOak\Models\User;
use DarkOak\Contracts\Repository\UserRepositoryInterface;

class UserRepository extends EloquentRepository implements UserRepositoryInterface
{
    /**
     * Return the model backing this repository.
     */
    public function model(): string
    {
        return User::class;
    }
}


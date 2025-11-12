<?php

namespace DarkOak\Repositories\Eloquent;

use DarkOak\Models\RecoveryToken;

class RecoveryTokenRepository extends EloquentRepository
{
    public function model(): string
    {
        return RecoveryToken::class;
    }
}


<?php

namespace DarkOak\Events\User;

use DarkOak\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

final class PasswordChanged
{
    use Dispatchable;

    public function __construct(public readonly User $user)
    {
    }
}

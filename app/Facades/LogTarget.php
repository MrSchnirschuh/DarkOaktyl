<?php

namespace DarkOak\Facades;

use Illuminate\Support\Facades\Facade;
use DarkOak\Services\Activity\ActivityLogTargetableService;

class LogTarget extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ActivityLogTargetableService::class;
    }
}


<?php

namespace DarkOak\Facades;

use Illuminate\Support\Facades\Facade;
use DarkOak\Services\Activity\ActivityLogBatchService;

class LogBatch extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ActivityLogBatchService::class;
    }
}


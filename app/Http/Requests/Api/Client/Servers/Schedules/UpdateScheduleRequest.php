<?php

namespace DarkOak\Http\Requests\Api\Client\Servers\Schedules;

use DarkOak\Models\Permission;

class UpdateScheduleRequest extends StoreScheduleRequest
{
    public function permission(): string
    {
        return Permission::ACTION_SCHEDULE_UPDATE;
    }
}


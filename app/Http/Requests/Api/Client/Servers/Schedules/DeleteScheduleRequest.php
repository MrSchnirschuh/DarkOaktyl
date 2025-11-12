<?php

namespace DarkOak\Http\Requests\Api\Client\Servers\Schedules;

use DarkOak\Models\Permission;

class DeleteScheduleRequest extends ViewScheduleRequest
{
    public function permission(): string
    {
        return Permission::ACTION_SCHEDULE_DELETE;
    }
}


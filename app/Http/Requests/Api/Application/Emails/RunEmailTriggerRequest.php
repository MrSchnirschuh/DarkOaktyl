<?php

namespace DarkOak\Http\Requests\Api\Application\Emails;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class RunEmailTriggerRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::EMAILS_TRIGGERS;
    }

    public function rules(): array
    {
        return [];
    }
}


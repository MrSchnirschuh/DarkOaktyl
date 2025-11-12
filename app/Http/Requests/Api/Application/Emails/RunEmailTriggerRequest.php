<?php

namespace Everest\Http\Requests\Api\Application\Emails;

use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

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

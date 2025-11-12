<?php

namespace DarkOak\Http\Requests\Api\Application\Emails;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class GetEmailTemplatesRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::EMAILS_READ;
    }

    public function rules(): array
    {
        return [];
    }
}


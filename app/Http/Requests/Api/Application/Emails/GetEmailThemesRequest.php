<?php

namespace Everest\Http\Requests\Api\Application\Emails;

use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class GetEmailThemesRequest extends ApplicationApiRequest
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

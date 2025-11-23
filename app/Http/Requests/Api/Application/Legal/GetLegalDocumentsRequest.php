<?php

namespace DarkOak\Http\Requests\Api\Application\Legal;

use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;
use DarkOak\Models\AdminRole;

class GetLegalDocumentsRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::LEGAL_READ;
    }

    public function rules(): array
    {
        return [];
    }
}

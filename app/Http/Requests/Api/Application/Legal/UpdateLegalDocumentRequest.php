<?php

namespace DarkOak\Http\Requests\Api\Application\Legal;

use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;
use DarkOak\Models\AdminRole;

class UpdateLegalDocumentRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::LEGAL_UPDATE;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'is_published' => 'required|boolean',
        ];
    }
}

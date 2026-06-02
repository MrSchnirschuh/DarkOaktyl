<?php

namespace DarkOak\Http\Requests\Api\Application\Links;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteLinkRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::LINKS_DELETE;
    }
}

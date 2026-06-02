<?php

namespace DarkOak\Http\Requests\Api\Application\Links;

use DarkOak\Models\AdminRole;
use DarkOak\Models\CustomLink;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreLinkRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return CustomLink::rules();
    }

    public function permission(): string
    {
        return AdminRole::LINKS_CREATE;
    }
}

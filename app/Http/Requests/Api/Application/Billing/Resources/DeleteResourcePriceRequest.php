<?php

namespace Everest\Http\Requests\Api\Application\Billing\Resources;

use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteResourcePriceRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_RESOURCES_DELETE;
    }
}

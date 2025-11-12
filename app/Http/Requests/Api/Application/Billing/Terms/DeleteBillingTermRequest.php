<?php

namespace Everest\Http\Requests\Api\Application\Billing\Terms;

use Everest\Models\AdminRole;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteBillingTermRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_TERMS_DELETE;
    }
}

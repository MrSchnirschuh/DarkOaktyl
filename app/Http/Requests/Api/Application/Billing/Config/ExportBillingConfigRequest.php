<?php

namespace DarkOak\Http\Requests\Api\Application\Billing\Config;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class ExportBillingConfigRequest extends ApplicationApiRequest
{
    public function permission(): string
    {
        return AdminRole::BILLING_EXPORT;
    }
}


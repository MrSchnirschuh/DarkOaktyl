<?php

namespace DarkOak\Http\Requests\Api\Client\Billing;

use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class ProcessUpgradeRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return [
            'product_id' => 'required|int|exists:products,id',
        ];
    }
}

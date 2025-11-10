<?php

namespace Everest\Http\Requests\Api\Client\Billing;

use Everest\Http\Requests\Api\Client\ClientApiRequest;

class CalculatePriceRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return [
            'pricing_configuration_id' => 'required|exists:pricing_configurations,id',
            'cpu' => 'required|integer|min:0',
            'memory' => 'required|integer|min:0',
            'disk' => 'required|integer|min:0',
            'backups' => 'required|integer|min:0',
            'databases' => 'required|integer|min:0',
            'allocations' => 'required|integer|min:1',
            'duration_days' => 'nullable|integer|min:1',
        ];
    }
}

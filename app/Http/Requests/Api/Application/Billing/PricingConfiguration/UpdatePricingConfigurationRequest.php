<?php

namespace Everest\Http\Requests\Api\Application\Billing\PricingConfiguration;

use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdatePricingConfigurationRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|min:3|max:191',
            'enabled' => 'sometimes|boolean',
            'cpu_price' => 'sometimes|numeric|min:0',
            'memory_price' => 'sometimes|numeric|min:0',
            'disk_price' => 'sometimes|numeric|min:0',
            'backup_price' => 'sometimes|numeric|min:0',
            'database_price' => 'sometimes|numeric|min:0',
            'allocation_price' => 'sometimes|numeric|min:0',
            'small_package_factor' => 'sometimes|numeric|min:0',
            'medium_package_factor' => 'sometimes|numeric|min:0',
            'large_package_factor' => 'sometimes|numeric|min:0',
            'small_package_threshold' => 'sometimes|integer|min:0',
            'large_package_threshold' => 'sometimes|integer|min:0',
            'durations' => 'nullable|array',
            'durations.*.id' => 'sometimes|exists:pricing_durations,id',
            'durations.*.duration_days' => 'required|integer|min:1',
            'durations.*.price_factor' => 'required|numeric|min:0',
            'durations.*.enabled' => 'boolean',
        ];
    }
}

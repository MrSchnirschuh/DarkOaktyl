<?php

namespace Everest\Http\Requests\Api\Application\Billing\PricingConfiguration;

use Everest\Http\Requests\Api\Application\ApplicationApiRequest;

class StorePricingConfigurationRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:191',
            'enabled' => 'boolean',
            'cpu_price' => 'required|numeric|min:0',
            'memory_price' => 'required|numeric|min:0',
            'disk_price' => 'required|numeric|min:0',
            'backup_price' => 'required|numeric|min:0',
            'database_price' => 'required|numeric|min:0',
            'allocation_price' => 'required|numeric|min:0',
            'small_package_factor' => 'required|numeric|min:0',
            'medium_package_factor' => 'required|numeric|min:0',
            'large_package_factor' => 'required|numeric|min:0',
            'small_package_threshold' => 'required|integer|min:0',
            'large_package_threshold' => 'required|integer|min:0',
            'durations' => 'nullable|array',
            'durations.*.duration_days' => 'required|integer|min:1',
            'durations.*.price_factor' => 'required|numeric|min:0',
            'durations.*.enabled' => 'boolean',
        ];
    }
}

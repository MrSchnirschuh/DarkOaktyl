<?php

namespace Everest\Transformers\Api\Application;

use Everest\Models\Billing\PricingConfiguration;
use Everest\Transformers\Api\Transformer;
use League\Fractal\Resource\Collection;

class PricingConfigurationTransformer extends Transformer
{
    protected array $availableIncludes = ['durations'];

    /**
     * Return the resource name for the transformer.
     */
    public function getResourceName(): string
    {
        return PricingConfiguration::RESOURCE_NAME;
    }

    /**
     * Transform a pricing configuration into a representation suitable for the API.
     */
    public function transform(PricingConfiguration $model): array
    {
        return [
            'id' => $model->id,
            'uuid' => $model->uuid,
            'name' => $model->name,
            'enabled' => $model->enabled,
            'cpu_price' => $model->cpu_price,
            'memory_price' => $model->memory_price,
            'disk_price' => $model->disk_price,
            'backup_price' => $model->backup_price,
            'database_price' => $model->database_price,
            'allocation_price' => $model->allocation_price,
            'small_package_factor' => $model->small_package_factor,
            'medium_package_factor' => $model->medium_package_factor,
            'large_package_factor' => $model->large_package_factor,
            'small_package_threshold' => $model->small_package_threshold,
            'large_package_threshold' => $model->large_package_threshold,
            'created_at' => self::formatTimestamp($model->created_at),
            'updated_at' => self::formatTimestamp($model->updated_at),
        ];
    }

    /**
     * Include the pricing durations for this configuration.
     */
    public function includeDurations(PricingConfiguration $model): Collection
    {
        return $this->collection($model->durations, new PricingDurationTransformer());
    }
}

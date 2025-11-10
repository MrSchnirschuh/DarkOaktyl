<?php

namespace Everest\Http\Controllers\Api\Application\Billing;

use Ramsey\Uuid\Uuid;
use Everest\Facades\Activity;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Everest\Models\Billing\PricingConfiguration;
use Everest\Models\Billing\PricingDuration;
use Spatie\QueryBuilder\QueryBuilder;
use Everest\Transformers\Api\Application\PricingConfigurationTransformer;
use Everest\Exceptions\Http\QueryValueOutOfRangeHttpException;
use Everest\Http\Controllers\Api\Application\ApplicationApiController;
use Everest\Http\Requests\Api\Application\Billing\PricingConfiguration\GetPricingConfigurationRequest;
use Everest\Http\Requests\Api\Application\Billing\PricingConfiguration\GetPricingConfigurationsRequest;
use Everest\Http\Requests\Api\Application\Billing\PricingConfiguration\StorePricingConfigurationRequest;
use Everest\Http\Requests\Api\Application\Billing\PricingConfiguration\DeletePricingConfigurationRequest;
use Everest\Http\Requests\Api\Application\Billing\PricingConfiguration\UpdatePricingConfigurationRequest;

class PricingConfigurationController extends ApplicationApiController
{
    /**
     * PricingConfigurationController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all pricing configurations.
     */
    public function index(GetPricingConfigurationsRequest $request): array
    {
        $perPage = (int) $request->query('per_page', '20');
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $configurations = QueryBuilder::for(PricingConfiguration::query())
            ->allowedFilters(['id', 'name', 'enabled'])
            ->allowedSorts(['id', 'name', 'created_at'])
            ->with('durations')
            ->paginate($perPage);

        return $this->fractal->collection($configurations)
            ->transformWith(PricingConfigurationTransformer::class)
            ->toArray();
    }

    /**
     * Store a new pricing configuration in the database.
     */
    public function store(StorePricingConfigurationRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $configuration = PricingConfiguration::create([
                'uuid' => Uuid::uuid4()->toString(),
                'name' => $request->input('name'),
                'enabled' => $request->input('enabled', true),
                'cpu_price' => $request->input('cpu_price'),
                'memory_price' => $request->input('memory_price'),
                'disk_price' => $request->input('disk_price'),
                'backup_price' => $request->input('backup_price'),
                'database_price' => $request->input('database_price'),
                'allocation_price' => $request->input('allocation_price'),
                'small_package_factor' => $request->input('small_package_factor', 1.0),
                'medium_package_factor' => $request->input('medium_package_factor', 1.0),
                'large_package_factor' => $request->input('large_package_factor', 1.0),
                'small_package_threshold' => $request->input('small_package_threshold', 2048),
                'large_package_threshold' => $request->input('large_package_threshold', 8192),
            ]);

            // Create duration options if provided
            if ($request->has('durations')) {
                foreach ($request->input('durations') as $duration) {
                    PricingDuration::create([
                        'pricing_configuration_id' => $configuration->id,
                        'duration_days' => $duration['duration_days'],
                        'price_factor' => $duration['price_factor'],
                        'enabled' => $duration['enabled'] ?? true,
                    ]);
                }
            }

            Activity::event('admin:billing:pricing:create')
                ->property('pricing_configuration', $configuration)
                ->description('A new pricing configuration was created')
                ->log();

            Cache::forget('application.billing.analytics');

            return $this->fractal->item($configuration->load('durations'))
                ->transformWith(PricingConfigurationTransformer::class)
                ->respond(Response::HTTP_CREATED);
        });
    }

    /**
     * Update an existing pricing configuration.
     */
    public function update(UpdatePricingConfigurationRequest $request, int $configId): Response
    {
        return DB::transaction(function () use ($request, $configId) {
            $configuration = PricingConfiguration::findOrFail($configId);

            $configuration->update($request->only([
                'name',
                'enabled',
                'cpu_price',
                'memory_price',
                'disk_price',
                'backup_price',
                'database_price',
                'allocation_price',
                'small_package_factor',
                'medium_package_factor',
                'large_package_factor',
                'small_package_threshold',
                'large_package_threshold',
            ]));

            // Update or create durations if provided
            if ($request->has('durations')) {
                foreach ($request->input('durations') as $durationData) {
                    if (isset($durationData['id'])) {
                        $duration = PricingDuration::findOrFail($durationData['id']);
                        $duration->update([
                            'duration_days' => $durationData['duration_days'],
                            'price_factor' => $durationData['price_factor'],
                            'enabled' => $durationData['enabled'] ?? true,
                        ]);
                    } else {
                        PricingDuration::create([
                            'pricing_configuration_id' => $configuration->id,
                            'duration_days' => $durationData['duration_days'],
                            'price_factor' => $durationData['price_factor'],
                            'enabled' => $durationData['enabled'] ?? true,
                        ]);
                    }
                }
            }

            Activity::event('admin:billing:pricing:update')
                ->property('pricing_configuration', $configuration)
                ->property('new_data', $request->all())
                ->description('A pricing configuration has been updated')
                ->log();

            Cache::forget('application.billing.analytics');

            return $this->returnNoContent();
        });
    }

    /**
     * View an existing pricing configuration.
     */
    public function view(GetPricingConfigurationRequest $request, int $configId): array
    {
        $configuration = PricingConfiguration::with('durations')->findOrFail($configId);

        return $this->fractal->item($configuration)
            ->transformWith(PricingConfigurationTransformer::class)
            ->toArray();
    }

    /**
     * Delete a pricing configuration.
     */
    public function delete(DeletePricingConfigurationRequest $request, int $configId): Response
    {
        $configuration = PricingConfiguration::findOrFail($configId);

        $configuration->delete();

        Activity::event('admin:billing:pricing:delete')
            ->property('pricing_configuration', $configuration)
            ->description('A pricing configuration has been deleted')
            ->log();

        Cache::forget('application.billing.analytics');

        return $this->returnNoContent();
    }
}

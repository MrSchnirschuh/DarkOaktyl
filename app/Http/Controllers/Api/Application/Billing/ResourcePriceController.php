<?php

namespace DarkOak\Http\Controllers\Api\Application\Billing;

use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use DarkOak\Models\Billing\ResourcePrice;
use Spatie\QueryBuilder\QueryBuilder;
use DarkOak\Transformers\Api\Application\ResourcePriceTransformer;
use DarkOak\Exceptions\Http\QueryValueOutOfRangeHttpException;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Resources\GetResourcePriceRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Resources\GetResourcePricesRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Resources\StoreResourcePriceRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Resources\UpdateResourcePriceRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Resources\DeleteResourcePriceRequest;

class ResourcePriceController extends ApplicationApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(GetResourcePricesRequest $request): array
    {
        $perPage = (int) $request->query('per_page', '50');
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $resources = QueryBuilder::for(ResourcePrice::query()->with('scalingRules'))
            ->allowedFilters(['resource', 'display_name', 'is_visible', 'is_metered'])
            ->allowedSorts(['sort_order', 'display_name', 'resource', 'price', 'created_at'])
            ->paginate($perPage);

        return $this->fractal->collection($resources)
            ->transformWith(ResourcePriceTransformer::class)
            ->toArray();
    }

    public function store(StoreResourcePriceRequest $request): JsonResponse
    {
        $resource = DB::transaction(function () use ($request) {
            $attributes = $this->extractAttributes($request, true);

            /** @var ResourcePrice $resource */
            $resource = ResourcePrice::query()->create($attributes);

            $this->syncScalingRules($resource, $request);

            return $resource->fresh(['scalingRules']);
        });

        Activity::event('admin:billing:resources:create')
            ->property('resource', $resource)
            ->description('A billing resource price was created')
            ->log();

        Cache::forget('application.billing.analytics');

        return $this->fractal->item($resource)
            ->transformWith(ResourcePriceTransformer::class)
            ->respond(Response::HTTP_CREATED);
    }

    public function view(GetResourcePriceRequest $request, ResourcePrice $resource): array
    {
        $resource->loadMissing('scalingRules');

        return $this->fractal->item($resource)
            ->transformWith(ResourcePriceTransformer::class)
            ->toArray();
    }

    public function update(UpdateResourcePriceRequest $request, ResourcePrice $resource): Response
    {
        DB::transaction(function () use ($request, $resource) {
            $attributes = $this->extractAttributes($request, false);

            if (!empty($attributes)) {
                $resource->fill($attributes);
                $resource->save();
            }

            $this->syncScalingRules($resource, $request);
        });

        Activity::event('admin:billing:resources:update')
            ->property('resource', $resource->fresh())
            ->property('new_data', $request->all())
            ->description('A billing resource price was updated')
            ->log();

        Cache::forget('application.billing.analytics');

        return $this->returnNoContent();
    }

    public function delete(DeleteResourcePriceRequest $request, ResourcePrice $resource): Response
    {
        $resource->delete();

        Activity::event('admin:billing:resources:delete')
            ->property('resource', $resource)
            ->description('A billing resource price was deleted')
            ->log();

        Cache::forget('application.billing.analytics');

        return $this->returnNoContent();
    }

    /**
     * @param StoreResourcePriceRequest|UpdateResourcePriceRequest $request
     */
    protected function extractAttributes(ApplicationApiRequest $request, bool $creating): array
    {
        $fields = [
            'resource',
            'display_name',
            'description',
            'unit',
            'base_quantity',
            'price',
            'currency',
            'min_quantity',
            'max_quantity',
            'default_quantity',
            'step_quantity',
            'is_visible',
            'is_metered',
            'sort_order',
            'metadata',
        ];

        $attributes = [];

        foreach ($fields as $field) {
            if ($request->exists($field)) {
                $value = $request->input($field);

                if ($field === 'currency') {
                    $value = $value ? strtoupper($value) : null;
                }

                if (in_array($field, ['is_visible', 'is_metered'], true) && $value !== null) {
                    $value = (bool) $value;
                }

                $attributes[$field] = $value;
            }
        }

        if ($creating) {
            $attributes['currency'] = $attributes['currency'] ?? strtoupper(config('modules.billing.currency.code', 'USD'));
            $attributes['is_visible'] = $attributes['is_visible'] ?? true;
            $attributes['is_metered'] = $attributes['is_metered'] ?? false;

            if (!array_key_exists('metadata', $attributes)) {
                $attributes['metadata'] = null;
            }
        } else {
            if (array_key_exists('currency', $attributes) && $attributes['currency'] === null) {
                $attributes['currency'] = strtoupper(config('modules.billing.currency.code', 'USD'));
            }
        }

        return $attributes;
    }

    /**
     * @param StoreResourcePriceRequest|UpdateResourcePriceRequest $request
     */
    protected function syncScalingRules(ResourcePrice $resource, ApplicationApiRequest $request): void
    {
        if (!$request->has('scaling_rules')) {
            return;
        }

        $rules = collect($request->input('scaling_rules', []));
        $keepIds = [];

        foreach ($rules as $rule) {
            $payload = [
                'threshold' => $rule['threshold'],
                'multiplier' => $rule['multiplier'],
                'mode' => $rule['mode'] ?? 'multiplier',
                'label' => $rule['label'] ?? null,
                'metadata' => $rule['metadata'] ?? null,
            ];

            if (!empty($rule['id'])) {
                $existing = $resource->scalingRules()->whereKey($rule['id'])->first();
                if ($existing) {
                    $existing->update($payload);
                    $keepIds[] = $existing->id;
                    continue;
                }
            }

            $created = $resource->scalingRules()->create($payload);
            $keepIds[] = $created->id;
        }

        if (!empty($keepIds)) {
            $resource->scalingRules()->whereNotIn('id', $keepIds)->delete();
        } else {
            $resource->scalingRules()->delete();
        }
    }
}


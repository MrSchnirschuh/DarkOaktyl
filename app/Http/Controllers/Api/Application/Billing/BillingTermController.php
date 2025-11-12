<?php

namespace DarkOak\Http\Controllers\Api\Application\Billing;

use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use DarkOak\Models\Billing\BillingTerm;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\QueryBuilder;
use DarkOak\Transformers\Api\Application\BillingTermTransformer;
use DarkOak\Exceptions\Http\QueryValueOutOfRangeHttpException;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Terms\GetBillingTermRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Terms\GetBillingTermsRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Terms\StoreBillingTermRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Terms\UpdateBillingTermRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Terms\DeleteBillingTermRequest;

class BillingTermController extends ApplicationApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(GetBillingTermsRequest $request): array
    {
        $perPage = (int) $request->query('per_page', '50');
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $terms = QueryBuilder::for(BillingTerm::query())
            ->allowedFilters(['name', 'slug', 'is_active', 'is_default'])
            ->allowedSorts(['sort_order', 'name', 'duration_days', 'created_at'])
            ->paginate($perPage);

        return $this->fractal->collection($terms)
            ->transformWith(BillingTermTransformer::class)
            ->toArray();
    }

    public function store(StoreBillingTermRequest $request): JsonResponse
    {
        $term = DB::transaction(function () use ($request) {
            $attributes = $this->extractAttributes($request, true);

            /** @var BillingTerm $term */
            $term = BillingTerm::query()->create($attributes);

            if ($term->is_default) {
                $this->demoteOtherDefaults($term->id);
            }

            return $term;
        });

        Activity::event('admin:billing:terms:create')
            ->property('term', $term)
            ->description('A billing term was created')
            ->log();

        Cache::forget('application.billing.analytics');

        return $this->fractal->item($term)
            ->transformWith(BillingTermTransformer::class)
            ->respond(Response::HTTP_CREATED);
    }

    public function view(GetBillingTermRequest $request, BillingTerm $term): array
    {
        return $this->fractal->item($term)
            ->transformWith(BillingTermTransformer::class)
            ->toArray();
    }

    public function update(UpdateBillingTermRequest $request, BillingTerm $term): Response
    {
        DB::transaction(function () use ($request, $term) {
            $attributes = $this->extractAttributes($request, false, $term);

            if (!empty($attributes)) {
                $term->fill($attributes);
                $term->save();
            }

            if (array_key_exists('is_default', $attributes) && $term->is_default) {
                $this->demoteOtherDefaults($term->id);
            }
        });

        Activity::event('admin:billing:terms:update')
            ->property('term', $term->fresh())
            ->property('new_data', $request->all())
            ->description('A billing term was updated')
            ->log();

        Cache::forget('application.billing.analytics');

        return $this->returnNoContent();
    }

    public function delete(DeleteBillingTermRequest $request, BillingTerm $term): Response
    {
        $term->delete();

        Activity::event('admin:billing:terms:delete')
            ->property('term', $term)
            ->description('A billing term was deleted')
            ->log();

        Cache::forget('application.billing.analytics');

        return $this->returnNoContent();
    }

    /**
     * @param StoreBillingTermRequest|UpdateBillingTermRequest $request
     */
    protected function extractAttributes(ApplicationApiRequest $request, bool $creating, ?BillingTerm $term = null): array
    {
        $fields = [
            'name',
            'slug',
            'duration_days',
            'multiplier',
            'is_active',
            'is_default',
            'sort_order',
            'metadata',
        ];

        $attributes = [];

        foreach ($fields as $field) {
            if ($request->exists($field)) {
                $value = $request->input($field);

                if (in_array($field, ['is_active', 'is_default'], true) && $value !== null) {
                    $value = (bool) $value;
                }

                if ($field === 'multiplier' && $value !== null) {
                    $value = (float) $value;
                }

                $attributes[$field] = $value;
            }
        }

        if ($creating) {
            if (!array_key_exists('slug', $attributes) || empty($attributes['slug'])) {
                $generatedSlug = null;

                if ($request->input('slug')) {
                    $generatedSlug = Str::slug($request->input('slug'));
                } elseif ($request->input('name')) {
                    $generatedSlug = Str::slug($request->input('name'));
                }

                if (!$generatedSlug) {
                    $generatedSlug = Str::slug(Str::uuid()->toString());
                }

                $attributes['slug'] = $generatedSlug;
            }

            $attributes['is_active'] = $attributes['is_active'] ?? true;
            $attributes['is_default'] = $attributes['is_default'] ?? false;

            if (!array_key_exists('metadata', $attributes)) {
                $attributes['metadata'] = null;
            }
        } else {
            if (array_key_exists('slug', $attributes) && empty($attributes['slug'])) {
                $fallback = $attributes['name'] ?? $request->input('name') ?? $term?->name;

                if ($fallback) {
                    $attributes['slug'] = Str::slug($fallback);
                } else {
                    unset($attributes['slug']);
                }
            }
        }

        return $attributes;
    }

    protected function demoteOtherDefaults(int $termId): void
    {
        BillingTerm::query()->where('id', '!=', $termId)->where('is_default', true)->update(['is_default' => false]);
    }
}


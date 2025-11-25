<?php

namespace DarkOak\Http\Controllers\Api\Application\Domains;

use DarkOak\Exceptions\Http\QueryValueOutOfRangeHttpException;
use DarkOak\Facades\Activity;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Models\DomainRoot;
use DarkOak\Transformers\Api\Application\DomainRootTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DomainRootController extends ApplicationApiController
{
    public function index(Request $request): array
    {
        $perPage = (int) $request->query('per_page', 25);
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $roots = QueryBuilder::for(DomainRoot::query())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::partial('name'),
                AllowedFilter::partial('root_domain'),
                AllowedFilter::exact('provider'),
                AllowedFilter::exact('is_active'),
            ])
            ->allowedSorts(['id', 'name', 'root_domain', 'provider', 'is_active', 'created_at'])
            ->paginate($perPage);

        return $this->fractal->collection($roots)
            ->transformWith(DomainRootTransformer::class)
            ->toArray();
    }

    public function store(Request $request): array
    {
        $data = $this->validatedData($request);

        $root = DomainRoot::query()->create($data);

        Activity::event('admin:domains:root:create')
            ->property('name', $root->name)
            ->property('root_domain', $root->root_domain)
            ->log();

        return $this->fractal->item($root)
            ->transformWith(DomainRootTransformer::class)
            ->toArray();
    }

    public function update(Request $request, int $id): Response
    {
        $root = DomainRoot::query()->findOrFail($id);
        $data = $this->validatedData($request, $root);

        $root->update($data);

        Activity::event('admin:domains:root:update')
            ->property('name', $root->name)
            ->property('root_domain', $root->root_domain)
            ->log();

        return $this->returnNoContent();
    }

    public function delete(int $id): Response
    {
        $root = DomainRoot::query()->findOrFail($id);
        $root->delete();

        Activity::event('admin:domains:root:delete')
            ->property('name', $root->name)
            ->property('root_domain', $root->root_domain)
            ->log();

        return $this->returnNoContent();
    }

    private function validatedData(Request $request, ?DomainRoot $root = null): array
    {
        $rules = $root ? DomainRoot::getRulesForUpdate($root, 'id') : DomainRoot::getRules();
        $data = $this->validate($request, $rules);

        $providerConfig = $data['provider_config'] ?? [];
        if (!is_array($providerConfig)) {
            $providerConfig = [];
        }

        $data['provider_config'] = $this->cleanProviderConfig($providerConfig);
        $data['is_active'] = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true;
        $data['provider'] = $data['provider'] ?? 'manual';

        return $data;
    }

    private function cleanProviderConfig(array $config): array
    {
        return collect($config)
            ->reject(fn ($value) => $value === '' || $value === null)
            ->toArray();
    }
}

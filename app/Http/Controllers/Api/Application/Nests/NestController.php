<?php

namespace DarkOak\Http\Controllers\Api\Application\Nests;

use DarkOak\Models\Nest;
use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\QueryBuilder;
use DarkOak\Services\Nests\NestUpdateService;
use DarkOak\Services\Nests\NestCreationService;
use DarkOak\Services\Nests\NestDeletionService;
use DarkOak\Services\Eggs\Sharing\EggImporterService;
use DarkOak\Transformers\Api\Application\EggTransformer;
use DarkOak\Transformers\Api\Application\NestTransformer;
use DarkOak\Exceptions\Http\QueryValueOutOfRangeHttpException;
use DarkOak\Http\Requests\Api\Application\Nests\GetNestRequest;
use DarkOak\Http\Requests\Api\Application\Eggs\ImportEggRequest;
use DarkOak\Http\Requests\Api\Application\Nests\GetNestsRequest;
use DarkOak\Http\Requests\Api\Application\Nests\StoreNestRequest;
use DarkOak\Http\Requests\Api\Application\Nests\DeleteNestRequest;
use DarkOak\Http\Requests\Api\Application\Nests\UpdateNestRequest;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;

class NestController extends ApplicationApiController
{
    /**
     * NestController constructor.
     */
    public function __construct(
        private NestCreationService $nestCreationService,
        private NestDeletionService $nestDeletionService,
        private NestUpdateService $nestUpdateService,
        private EggImporterService $eggImporterService
    ) {
        parent::__construct();
    }

    /**
     * Return all Nests that exist on the Panel.
     */
    public function index(GetNestsRequest $request): array
    {
        $perPage = (int) $request->query('per_page', '20');
        if ($perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $nests = QueryBuilder::for(Nest::query())
            ->allowedFilters(['id', 'name', 'author'])
            ->allowedSorts(['id', 'name', 'author']);
        if ($perPage > 0) {
            $nests = $nests->paginate($perPage);
        }

        return $this->fractal->collection($nests)
            ->transformWith(NestTransformer::class)
            ->toArray();
    }

    /**
     * Return information about a single Nest model.
     */
    public function view(GetNestRequest $request, Nest $nest): array
    {
        return $this->fractal->item($nest)
            ->transformWith(NestTransformer::class)
            ->toArray();
    }

    /**
     * Creates a new nest.
     *
     * @throws \DarkOak\Exceptions\Model\DataValidationException
     */
    public function store(StoreNestRequest $request): array
    {
        $nest = $this->nestCreationService->handle($request->validated());

        Activity::event('admin:nests:create')
            ->property('nest', $nest)
            ->description('A nest was created')
            ->log();

        return $this->fractal->item($nest)
            ->transformWith(NestTransformer::class)
            ->toArray();
    }

    /**
     * Imports an egg.
     */
    public function import(ImportEggRequest $request, Nest $nest): array
    {
        $egg = $this->eggImporterService->handleContent(
            $nest->id,
            $request->getContent(),
            $request->headers->get('Content-Type'),
        );

        Activity::event('admin:nests:import')
            ->property('egg', $egg)
            ->property('nest', $nest)
            ->description('An egg was imported to a nest')
            ->log();

        return $this->fractal->item($egg)
            ->transformWith(EggTransformer::class)
            ->toArray();
    }

    /**
     * Updates an existing nest.
     *
     * @throws \DarkOak\Exceptions\Model\DataValidationException
     * @throws \DarkOak\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UpdateNestRequest $request, Nest $nest): array
    {
        $this->nestUpdateService->handle($nest->id, $request->validated());

        Activity::event('admin:nests:update')
            ->property('nest', $nest)
            ->property('new_data', $request->all())
            ->description('A nest was updated')
            ->log();

        return $this->fractal->item($nest)
            ->transformWith(NestTransformer::class)
            ->toArray();
    }

    /**
     * Deletes an existing nest.
     *
     * @throws \DarkOak\Exceptions\Service\HasActiveServersException
     */
    public function delete(DeleteNestRequest $request, Nest $nest): Response
    {
        $this->nestDeletionService->handle($nest->id);

        Activity::event('admin:nests:delete')
            ->property('nest', $nest)
            ->description('A nest was deleted')
            ->log();

        return $this->returnNoContent();
    }
}


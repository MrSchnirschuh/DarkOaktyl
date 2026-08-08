<?php

namespace DarkOak\Http\Controllers\Api\Application\Databases;

use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
use DarkOak\Models\DatabaseHost;
use Spatie\QueryBuilder\QueryBuilder;
use DarkOak\Services\Databases\Hosts\HostUpdateService;
use DarkOak\Services\Databases\Hosts\HostCreationService;
use DarkOak\Exceptions\Http\QueryValueOutOfRangeHttpException;
use DarkOak\Transformers\Api\Application\DatabaseHostTransformer;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\Databases\GetDatabaseRequest;
use DarkOak\Http\Requests\Api\Application\Databases\GetDatabasesRequest;
use DarkOak\Http\Requests\Api\Application\Databases\StoreDatabaseRequest;
use DarkOak\Http\Requests\Api\Application\Databases\DeleteDatabaseRequest;
use DarkOak\Http\Requests\Api\Application\Databases\UpdateDatabaseRequest;

class DatabaseController extends ApplicationApiController
{
    /**
     * DatabaseController constructor.
     */
    public function __construct(private HostCreationService $creationService, private HostUpdateService $updateService)
    {
        parent::__construct();
    }

    /**
     * Returns an array of all database hosts.
     */
    public function index(GetDatabasesRequest $request): array
    {
        $perPage = (int) $request->query('per_page', '20');
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $databases = QueryBuilder::for(DatabaseHost::query())
            ->allowedFilters(...['name', 'host'])
            ->allowedSorts(...['id', 'name', 'host'])
            ->paginate($perPage);

        return $this->transform($databases, DatabaseHostTransformer::class);
    }

    /**
     * Returns a single database host.
     */
    public function view(GetDatabaseRequest $request, DatabaseHost $database): array
    {
        return $this->transform($database, DatabaseHostTransformer::class);
    }

    /**
     * Creates a new database host.
     *
     * @throws \Throwable
     */
    public function store(StoreDatabaseRequest $request): array
    {
        $database = $this->creationService->handle($request->validated());

        Activity::event('admin:database-hosts:create')
            ->subject($database)
            ->property('database-host', $database)
            ->description('A new database host was created')
            ->log();

        return $this->transform($database, DatabaseHostTransformer::class);
    }

    /**
     * Updates a database host.
     *
     * @throws \Throwable
     */
    public function update(UpdateDatabaseRequest $request, DatabaseHost $database): array
    {
        $database = $this->updateService->handle($database->id, $request->validated());

        Activity::event('admin:database-hosts:update')
            ->subject($database)
            ->property('database-host', $database)
            ->property('new_data', $request->all())
            ->description('A database host was updated')
            ->log();

        return $this->transform($database, DatabaseHostTransformer::class);
    }

    /**
     * Deletes a database host.
     *
     * @throws \Exception
     */
    public function delete(DeleteDatabaseRequest $request, DatabaseHost $database): Response
    {
        $database->delete();

        Activity::event('admin:database-hosts:delete')
            ->subject($database)
            ->property('database-host', $database)
            ->description('A database host was deleted')
            ->log();

        return $this->returnNoContent();
    }
}

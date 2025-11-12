<?php

namespace DarkOak\Http\Controllers\Api\Application\Servers;

use DarkOak\Models\Server;
use DarkOak\Models\Database;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use DarkOak\Services\Databases\DatabasePasswordService;
use DarkOak\Services\Databases\DatabaseManagementService;
use DarkOak\Transformers\Api\Application\ServerDatabaseTransformer;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\Servers\Databases\GetServerDatabaseRequest;
use DarkOak\Http\Requests\Api\Application\Servers\Databases\GetServerDatabasesRequest;
use DarkOak\Http\Requests\Api\Application\Servers\Databases\ServerDatabaseWriteRequest;
use DarkOak\Http\Requests\Api\Application\Servers\Databases\StoreServerDatabaseRequest;

class DatabaseController extends ApplicationApiController
{
    /**
     * DatabaseController constructor.
     */
    public function __construct(
        private DatabaseManagementService $databaseManagementService,
        private DatabasePasswordService $databasePasswordService
    ) {
        parent::__construct();
    }

    /**
     * Return a listing of all databases currently available to a single
     * server.
     */
    public function index(GetServerDatabasesRequest $request, Server $server): array
    {
        return $this->fractal->collection($server->databases)
            ->transformWith(ServerDatabaseTransformer::class)
            ->toArray();
    }

    /**
     * Return a single server database.
     */
    public function view(GetServerDatabaseRequest $request, Server $server, Database $database): array
    {
        return $this->fractal->item($database)
            ->transformWith(ServerDatabaseTransformer::class)
            ->toArray();
    }

    /**
     * Reset the password for a specific server database.
     *
     * @throws \Throwable
     */
    public function resetPassword(ServerDatabaseWriteRequest $request, Server $server, Database $database): Response
    {
        $this->databasePasswordService->handle($database);

        return $this->returnNoContent();
    }

    /**
     * Create a new database on the Panel for a given server.
     *
     * @throws \Throwable
     */
    public function store(StoreServerDatabaseRequest $request, Server $server): JsonResponse
    {
        $database = $this->databaseManagementService->create($server, array_merge($request->validated(), [
            'database' => $request->databaseName(),
        ]));

        return $this->fractal->item($database)
            ->transformWith(ServerDatabaseTransformer::class)
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * Handle a request to delete a specific server database from the Panel.
     *
     * @throws \Exception
     */
    public function delete(ServerDatabaseWriteRequest $request, Database $database): Response
    {
        $this->databaseManagementService->delete($database);

        return $this->returnNoContent();
    }
}


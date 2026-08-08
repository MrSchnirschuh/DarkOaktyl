<?php

namespace DarkOak\Http\Controllers\Api\Application\Servers;

use DarkOak\Models\Server;
use DarkOak\Models\Database;
use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
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
        private DatabasePasswordService $databasePasswordService,
    ) {
        parent::__construct();
    }

    /**
     * Return a listing of all databases currently available to a single
     * server.
     */
    public function index(GetServerDatabasesRequest $request, Server $server): array
    {
        return $this->transform($server->databases, ServerDatabaseTransformer::class);
    }

    /**
     * Return a single server database.
     */
    public function view(GetServerDatabaseRequest $request, Server $server, Database $database): array
    {
        return $this->transform($database, ServerDatabaseTransformer::class);
    }

    /**
     * Reset the password for a specific server database.
     *
     * @throws \Throwable
     */
    public function resetPassword(ServerDatabaseWriteRequest $request, Server $server, Database $database): Response
    {
        $this->databasePasswordService->handle($database);

        Activity::event('admin:servers:databases:reset-password')
            ->subject($server, $database)
            ->property('server', $server)
            ->property('database', $database)
            ->description('A server database password was reset')
            ->log();

        return $this->returnNoContent();
    }

    /**
     * Create a new database on the Panel for a given server.
     *
     * @throws \Throwable
     */
    public function store(StoreServerDatabaseRequest $request, Server $server): array
    {
        $database = $this->databaseManagementService->create($server, array_merge($request->validated(), [
            'database' => $request->databaseName(),
        ]));

        Activity::event('admin:servers:databases:create')
            ->subject($server, $database)
            ->property('server', $server)
            ->property('database', $database)
            ->description('A database was created for a server')
            ->log();

        return $this->transform($database, ServerDatabaseTransformer::class);
    }

    /**
     * Handle a request to delete a specific server database from the Panel.
     *
     * @throws \Exception
     */
    public function delete(ServerDatabaseWriteRequest $request, Database $database): Response
    {
        $server = $database->server;

        $this->databaseManagementService->delete($database);

        Activity::event('admin:servers:databases:delete')
            ->subject($server, $database)
            ->property('server', $server)
            ->property('database', $database)
            ->description('A server database was deleted')
            ->log();

        return $this->returnNoContent();
    }
}

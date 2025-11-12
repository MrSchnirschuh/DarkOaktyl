<?php

namespace DarkOak\Services\Databases;

use DarkOak\Models\Server;
use DarkOak\Models\Database;
use Webmozart\Assert\Assert;
use DarkOak\Models\DatabaseHost;
use DarkOak\Exceptions\Service\Database\NoSuitableDatabaseHostException;

class DeployServerDatabaseService
{
    /**
     * DeployServerDatabaseService constructor.
     */
    public function __construct(private DatabaseManagementService $managementService)
    {
    }

    /**
     * @throws \Throwable
     * @throws \DarkOak\Exceptions\Service\Database\TooManyDatabasesException
     * @throws \DarkOak\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException
     */
    public function handle(Server $server, array $data): Database
    {
        Assert::notEmpty($data['database'] ?? null);
        Assert::notEmpty($data['remote'] ?? null);

        $databaseHostId = $server->node->database_host_id;
        if (is_null($databaseHostId)) {
            if (!config('DarkOak.client_features.databases.allow_random')) {
                throw new NoSuitableDatabaseHostException();
            }

            $hosts = DatabaseHost::query()->get()->toBase();
            if ($hosts->isEmpty()) {
                throw new NoSuitableDatabaseHostException();
            }

            /** @var \DarkOak\Models\DatabaseHost $databaseHost */
            $databaseHost = $hosts->random();
            $databaseHostId = $databaseHost->id;
        }

        return $this->managementService->create($server, [
            'database_host_id' => $databaseHostId,
            'database' => DatabaseManagementService::generateUniqueDatabaseName($data['database'], $server->id),
            'remote' => $data['remote'],
        ]);
    }
}


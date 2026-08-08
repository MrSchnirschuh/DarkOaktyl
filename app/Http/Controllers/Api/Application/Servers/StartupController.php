<?php

namespace DarkOak\Http\Controllers\Api\Application\Servers;

use DarkOak\Models\User;
use DarkOak\Models\Server;
use DarkOak\Facades\Activity;
use DarkOak\Services\Servers\StartupModificationService;
use DarkOak\Transformers\Api\Application\ServerTransformer;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\Servers\UpdateServerStartupRequest;

class StartupController extends ApplicationApiController
{
    /**
     * StartupController constructor.
     */
    public function __construct(private StartupModificationService $modificationService)
    {
        parent::__construct();
    }

    /**
     * Update the startup and environment settings for a specific server.
     *
     * @throws \Throwable
     */
    public function index(UpdateServerStartupRequest $request, Server $server): array
    {
        $server = $this->modificationService
            ->setUserLevel(User::USER_LEVEL_ADMIN)
            ->handle($server, $request->validated());

        Activity::event('admin:servers:startup')
            ->subject($server)
            ->property('server', $server)
            ->property('new_data', $request->all())
            ->description('A server startup configuration was updated')
            ->log();

        return $this->transform($server, ServerTransformer::class);

    }
}

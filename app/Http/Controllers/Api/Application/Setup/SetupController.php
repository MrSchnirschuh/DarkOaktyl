<?php

namespace DarkOak\Http\Controllers\Api\Application\Setup;

use DarkOak\Models\Setting;
use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use DarkOak\Http\Requests\Api\Application\OverviewRequest;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;

class SetupController extends ApplicationApiController
{
    /**
     * SetupController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all known data from existing database rows.
     *
     * @throws \Throwable
     */
    public function data(OverviewRequest $request): JsonResponse
    {
        return response()->json([
            'nodes' => \DarkOak\Models\Node::query()->count(),
            'servers' => \DarkOak\Models\Server::query()->count(),
            'users' => \DarkOak\Models\User::query()->count(),
            'eggs' => \DarkOak\Models\Egg::query()->count(),
        ]);
    }

    /**
     * Mark the panel as 'setup' and ready for use.
     *
     * @throws \Throwable
     */
    public function finish(OverviewRequest $request): Response
    {
        Setting::set('settings::app:setup', true);

        Activity::event('admin:setup:finish')
            ->description('The panel setup wizard was completed')
            ->log();

        return $this->returnNoContent();
    }
}

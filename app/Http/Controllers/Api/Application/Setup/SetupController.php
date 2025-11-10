<?php

namespace Everest\Http\Controllers\Api\Application\Setup;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Everest\Contracts\Repository\SettingsRepositoryInterface;
use Everest\Http\Controllers\Api\Application\ApplicationApiController;

class SetupController extends ApplicationApiController
{
    /**
     * SetupController constructor.
     */
    public function __construct(private SettingsRepositoryInterface $settings)
    {
        parent::__construct();
    }

    /**
     * Get all known data from existing database rows.
     *
     * @throws \Throwable
     */
    public function data(): JsonResponse
    {
        return response()->json([
            'nodes' => \Everest\Models\Node::query()->count(),
            'servers' => \Everest\Models\Server::query()->count(),
            'users' => \Everest\Models\User::query()->count(),
            'eggs' => \Everest\Models\Egg::query()->count(),
        ]);
    }

    /**
     * Mark the panel as 'setup' and ready for use.
     *
     * @throws \Throwable
     */
    public function finish(): Response
    {
        $this->settings->set('settings::app:setup', true);

        return $this->returnNoContent();
    }
}

<?php

namespace Everest\Http\Controllers\Api\Application;

use Everest\Models\Node;
use Everest\Models\Server;
use Everest\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Everest\Services\Helpers\SoftwareVersionService;

class OverviewController extends ApplicationApiController
{
    /**
     * OverviewController constructor.
     */
    public function __construct(
        private SoftwareVersionService $softwareVersionService
    ) {
        parent::__construct();
    }

    /**
     * Returns version information.
     */
    public function version(): JsonResponse
    {
        return new JsonResponse($this->softwareVersionService->getVersionData());
    }

    /**
     * Returns metrics relating to server count, user count & more.
     */
    public function metrics(): JsonResponse
    {
        $nodes = Node::query()->count();
        $servers = Server::query()->count();
        $tickets = Ticket::query()->where('status', 'pending')->count();

        $data = [
            'nodes' => $nodes,
            'servers' => $servers,
            'tickets' => $tickets,
        ];

        return new JsonResponse($data);
    }
}

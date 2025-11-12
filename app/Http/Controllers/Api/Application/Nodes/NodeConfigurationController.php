<?php

namespace DarkOak\Http\Controllers\Api\Application\Nodes;

use DarkOak\Models\Node;
use Illuminate\Http\JsonResponse;
use DarkOak\Http\Requests\Api\Application\Nodes\GetNodeRequest;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;

class NodeConfigurationController extends ApplicationApiController
{
    /**
     * Returns the configuration information for a node. This allows for automated deployments
     * to remote machines so long as an API key is provided to the machine to make the request
     * with, and the node is known.
     */
    public function __invoke(GetNodeRequest $request, Node $node): JsonResponse|string
    {
        if ($request->query('format') === 'yaml') {
            return $node->getYamlConfiguration();
        }

        return new JsonResponse($node->getConfiguration());
    }
}


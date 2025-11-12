<?php

namespace DarkOak\Http\Controllers\Api\Application\Nodes;

use DarkOak\Models\Node;
use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use DarkOak\Services\Nodes\NodeUpdateService;
use DarkOak\Services\Nodes\NodeCreationService;
use DarkOak\Services\Nodes\NodeDeletionService;
use DarkOak\Transformers\Api\Application\NodeTransformer;
use DarkOak\Exceptions\Http\QueryValueOutOfRangeHttpException;
use DarkOak\Http\Requests\Api\Application\Nodes\GetNodeRequest;
use DarkOak\Http\Requests\Api\Application\Nodes\GetNodesRequest;
use DarkOak\Http\Requests\Api\Application\Nodes\StoreNodeRequest;
use DarkOak\Http\Requests\Api\Application\Nodes\DeleteNodeRequest;
use DarkOak\Http\Requests\Api\Application\Nodes\UpdateNodeRequest;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;

class NodeController extends ApplicationApiController
{
    /**
     * NodeController constructor.
     */
    public function __construct(
        private NodeCreationService $creationService,
        private NodeDeletionService $deletionService,
        private NodeUpdateService $updateService
    ) {
        parent::__construct();
    }

    /**
     * Return all the nodes currently available on the Panel.
     */
    public function index(GetNodesRequest $request): array
    {
        $perPage = (int) $request->query('per_page', '20');
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $nodes = QueryBuilder::for(Node::query())
            ->allowedFilters(['id', 'uuid', 'name', 'fqdn', 'daemon_token_id'])
            ->allowedSorts(['id', 'uuid', 'name', 'fqdn', 'memory', 'disk'])
            ->paginate($perPage);

        return $this->fractal->collection($nodes)
            ->transformWith(NodeTransformer::class)
            ->toArray();
    }

    /**
     * Return data for a single instance of a node.
     */
    public function view(GetNodeRequest $request, Node $node): array
    {
        return $this->fractal->item($node)
            ->addMeta(['utilization' => $node->getPercentUtilization()])
            ->transformWith(NodeTransformer::class)
            ->toArray();
    }

    /**
     * Create a new node on the Panel. Returns the created node and an HTTP/201
     * status response on success.
     *
     * @throws \DarkOak\Exceptions\Model\DataValidationException
     */
    public function store(StoreNodeRequest $request): JsonResponse
    {
        $node = $this->creationService->handle($request->validated());

        Activity::event('admin:nodes:create')
            ->property('node', $node)
            ->description('A node was created')
            ->log();

        return $this->fractal->item($node)
            ->transformWith(NodeTransformer::class)
            ->respond(201);
    }

    /**
     * Update an existing node on the Panel.
     *
     * @throws \Throwable
     */
    public function update(UpdateNodeRequest $request, Node $node): array
    {
        $node = $this->updateService->handle(
            $node,
            $request->validated(),
        );

        Activity::event('admin:nodes:update')
            ->property('node', $node)
            ->property('new_data', $request->all())
            ->description('A node was updated')
            ->log();

        return $this->fractal->item($node)
            ->transformWith(NodeTransformer::class)
            ->toArray();
    }

    /**
     * Deletes a given node from the Panel as long as there are no servers
     * currently attached to it.
     *
     * @throws \DarkOak\Exceptions\Service\HasActiveServersException
     */
    public function delete(DeleteNodeRequest $request, Node $node): Response
    {
        $this->deletionService->handle($node);

        Activity::event('admin:nodes:delete')
            ->property('node', $node)
            ->description('A node was deleted')
            ->log();

        return $this->returnNoContent();
    }
}


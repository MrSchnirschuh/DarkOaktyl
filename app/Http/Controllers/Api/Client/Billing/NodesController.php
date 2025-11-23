<?php

namespace DarkOak\Http\Controllers\Api\Client\Billing;

use DarkOak\Models\Node;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use DarkOak\Models\Billing\Product;
use DarkOak\Models\Billing\BillingException;
use DarkOak\Transformers\Api\Client\NodeTransformer;
use DarkOak\Http\Controllers\Api\Client\ClientApiController;
use DarkOak\Repositories\Wings\DaemonConfigurationRepository;

class NodesController extends ClientApiController
{
    public function __construct(private DaemonConfigurationRepository $repository)
    {
        parent::__construct();
    }

    /**
     * Returns all the nodes that the server can be deployed to.
     */
    public function index(Request $request, Product $product): array
    {
        $free = (float) $product->price === 0.00;

        return $this->transformNodesResponse($this->gatherNodes($free), $free);
    }

    public function builder(Request $request): array
    {
        $type = strtolower((string) $request->query('type', 'paid'));
        $free = $type === 'free';

        return $this->transformNodesResponse($this->gatherNodes($free), $free);
    }

    private function gatherNodes(bool $free): Collection
    {
        $nodes = Node::where($free ? 'deployable_free' : 'deployable', true)->get();

        if ($nodes->isEmpty() && !$free) {
            BillingException::create([
                'title' => 'No deployable nodes found',
                'exception_type' => BillingException::TYPE_DEPLOYMENT,
                'description' => 'Ensure at least one node has the "deployable" box checked',
            ]);

            return collect();
        }

        $availableNodes = collect();

        foreach ($nodes as $node) {
            $hasFreeAllocation = $node->allocations()->whereNull('server_id')->exists();
            if (!$hasFreeAllocation) {
                continue;
            }

            try {
                $this->repository->setNode($node)->getSystemInformation();
            } catch (\Throwable $e) {
                continue;
            }

            $availableNodes->push($node);
        }

        if ($availableNodes->isEmpty()) {
            BillingException::create([
                'title' => 'No nodes satisfy requirements',
                'exception_type' => BillingException::TYPE_DEPLOYMENT,
                'description' => 'Available nodes are either offline or have zero free allocations',
            ]);
        }

        return $availableNodes;
    }

    private function transformNodesResponse(Collection $nodes, bool $free): array
    {
        if ($nodes->isEmpty() && $free) {
            return $this->fractal->collection(collect())
                ->transformWith(NodeTransformer::class)
                ->toArray();
        }

        return $this->fractal->collection($nodes)
            ->transformWith(NodeTransformer::class)
            ->toArray();
    }
}


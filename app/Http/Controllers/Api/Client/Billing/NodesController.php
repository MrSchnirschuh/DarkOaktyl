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
        $defaultType = (float) $product->price === 0.00 ? 'free' : 'paid';
        $type = $this->resolveDeploymentType($request->query('type'), $defaultType);

        return $this->transformNodesResponse($this->gatherNodes($type), $type);
    }

    public function builder(Request $request): array
    {
        $type = $this->resolveDeploymentType($request->query('type'), 'paid');

        return $this->transformNodesResponse($this->gatherNodes($type), $type);
    }

    private function gatherNodes(string $type): Collection
    {
        $column = match ($type) {
            'free' => 'deployable_free',
            'metered' => 'deployable_metered',
            default => 'deployable',
        };

        $nodes = Node::where($column, true)->get();

        if ($nodes->isEmpty() && $type !== 'free') {
            BillingException::create([
                'title' => 'No deployable nodes found',
                'exception_type' => BillingException::TYPE_DEPLOYMENT,
                'description' => $this->missingDeploymentTypeMessage($type),
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

        if ($availableNodes->isEmpty() && $type !== 'free') {
            BillingException::create([
                'title' => 'No nodes satisfy requirements',
                'exception_type' => BillingException::TYPE_DEPLOYMENT,
                'description' => 'Available nodes are either offline or have zero free allocations',
            ]);
        }

        return $availableNodes;
    }

    private function transformNodesResponse(Collection $nodes, string $type): array
    {
        if ($nodes->isEmpty() && $type === 'free') {
            return $this->fractal->collection(collect())
                ->transformWith(NodeTransformer::class)
                ->toArray();
        }

        return $this->fractal->collection($nodes)
            ->transformWith(NodeTransformer::class)
            ->toArray();
    }

    private function resolveDeploymentType(?string $requestedType, string $fallback): string
    {
        $type = strtolower((string) ($requestedType ?? $fallback));

        return in_array($type, ['paid', 'free', 'metered'], true) ? $type : $fallback;
    }

    private function missingDeploymentTypeMessage(string $type): string
    {
        return match ($type) {
            'metered' => 'Ensure at least one node has the "deployable for metered servers" option enabled',
            default => 'Ensure at least one node has the "deployable" box checked',
        };
    }
}


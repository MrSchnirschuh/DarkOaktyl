<?php

namespace DarkOak\Http\Controllers\Api\Client\Billing;

use DarkOak\Exceptions\DisplayException;
use DarkOak\Http\Controllers\Api\Client\ClientApiController;
use DarkOak\Http\Requests\Api\Client\Billing\Builder\BuilderFreeOrderRequest;
use DarkOak\Models\Billing\Order;
use DarkOak\Models\Billing\Category;
use DarkOak\Models\Node;
use DarkOak\Models\Server;
use DarkOak\Services\Billing\BuilderOrderService;
use DarkOak\Services\Billing\BuilderQuoteService;
use DarkOak\Services\Billing\CreateOrderService;
use DarkOak\Services\Billing\CreateServerService;
use DarkOak\Transformers\Api\Client\ServerTransformer;

class BuilderFreeOrderController extends ClientApiController
{
    public function __construct(
        private CreateServerService $serverCreation,
        private CreateOrderService $orderService,
        private BuilderQuoteService $builderQuotes,
        private BuilderOrderService $builderOrders,
    ) {
        parent::__construct();
    }

    public function process(BuilderFreeOrderRequest $request): array
    {
        if (config('modules.billing.enabled') !== '1') {
            throw new DisplayException('The billing module is not enabled.');
        }

        $category = Category::findOrFail($request->input('category_id'));
        $node = Node::findOrFail($request->input('node_id'));

        $quoteResult = $this->builderQuotes->calculateFromRequest($request);
        $total = $this->builderOrders->resolveTotal($quoteResult['quote']);
        $deploymentType = $quoteResult['deployment_type'] ?? 'free';

        $this->assertNodeDeployment($node, $deploymentType);

        if ($total > 0.0) {
            throw new DisplayException('This configuration requires payment. Use the checkout instead.');
        }

        $limits = $this->builderOrders->deriveLimits($quoteResult['quote']['resources'] ?? []);
        $metadata = $this->builderOrders->buildMetadata(
            $category,
            $node,
            $quoteResult['selections'],
            $quoteResult['quote'],
            $limits,
            $request->input('variables', []),
            $quoteResult['term'],
            $request->input('coupons', []),
            $deploymentType,
        );

        $order = $this->orderService->create(
            null,
            $request->user(),
            null,
            Order::STATUS_PENDING,
            $this->getOrderType($request),
            [
                'storefront' => Order::STOREFRONT_BUILDER,
                'billing_term_id' => $quoteResult['term']?->id,
                'node_id' => $node->id,
                'builder_metadata' => $metadata,
                'description' => substr($category->name, 0, 32) . ' builder order for ' . $request->user()->email,
                'total' => $total,
            ],
        );

        if ($order->type === Order::TYPE_REN && $request->filled('server_id')) {
            $server = Server::findOrFail((int) $request->input('server_id'));

            $server->update([
                'renewal_date' => $server->renewal_date->addDays(30),
                'status' => $server->isSuspended() ? null : $server->status,
            ]);
        } else {
            $server = $this->serverCreation->processBuilder($request, $metadata, $order);
        }

        $order->update([
            'status' => Order::STATUS_PROCESSED,
            'name' => $order->name . substr($server->uuid, 0, 8),
        ]);

        return $this->fractal->item($server)
            ->transformWith(ServerTransformer::class)
            ->toArray();
    }

    private function getOrderType(BuilderFreeOrderRequest $request): mixed
    {
        if ($request->boolean('renewal')) {
            return Order::TYPE_REN;
        }

        return Order::TYPE_NEW;
    }

    private function assertNodeDeployment(Node $node, string $type): void
    {
        if ($node->allowsDeploymentType($type)) {
            return;
        }

        $message = match ($type) {
            'metered' => 'Usage-billed servers cannot be deployed to this node.',
            'free' => 'Free servers cannot be deployed to this node.',
            default => 'Paid servers cannot be deployed to this node.',
        };

        throw new DisplayException($message);
    }
}

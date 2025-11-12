<?php

namespace DarkOak\Http\Controllers\Api\Application\Billing;

use DarkOak\Models\Billing\Order;
use Spatie\QueryBuilder\QueryBuilder;
use DarkOak\Transformers\Api\Application\OrderTransformer;
use DarkOak\Exceptions\Http\QueryValueOutOfRangeHttpException;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\Billing\Orders\GetBillingOrdersRequest;

class OrderController extends ApplicationApiController
{
    /**
     * OrderController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all orders.
     */
    public function index(GetBillingOrdersRequest $request): array
    {
        $perPage = (int) $request->query('per_page', '20');
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $orders = QueryBuilder::for(Order::query())
            ->allowedFilters(['id', 'name', 'description'])
            ->allowedSorts(['id', 'name', 'total', 'is_renewal', 'created_at', 'threat_index'])
            ->paginate($perPage);

        return $this->fractal->collection($orders)
            ->transformWith(OrderTransformer::class)
            ->toArray();
    }
}


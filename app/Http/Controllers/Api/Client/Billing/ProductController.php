<?php

namespace DarkOak\Http\Controllers\Api\Client\Billing;

use Illuminate\Support\Facades\Cache;
use DarkOak\Models\Billing\Product;
use DarkOak\Models\Billing\Category;
use DarkOak\Models\Billing\BillingException;
use DarkOak\Transformers\Api\Client\ProductTransformer;
use DarkOak\Http\Controllers\Api\Client\ClientApiController;

class ProductController extends ClientApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns all the products that have been configured.
     */
    public function index(int $id): array
    {
        $category = Cache::remember(
            "client.billing.category.{$id}",
            now()->addSeconds(60),
            static fn () => Category::findOrFail($id),
        );

        $products = Cache::remember(
            "client.billing.category.{$category->uuid}.products",
            now()->addSeconds(60),
            static fn () => Product::where('category_uuid', $category->uuid)->orderBy('price')->get(),
        );

        if ($products->isEmpty()) {
            BillingException::firstOrCreate(
                [
                    'title' => 'No products in category ' . $category->name . ' are visible',
                    'exception_type' => BillingException::TYPE_STOREFRONT,
                ],
                [
                    'description' => 'Go to this category and create a visible product',
                ],
            );
        }

        return $this->fractal->collection($products)
            ->transformWith(ProductTransformer::class)
            ->toArray();
    }

    /**
     * View a specific product.
     */
    public function view(int $id)
    {
        $product = Cache::remember(
            "client.billing.product.{$id}",
            now()->addSeconds(60),
            static fn () => Product::findOrFail($id),
        );

        return $this->fractal->item($product)
            ->transformWith(ProductTransformer::class)
            ->toArray();
    }
}


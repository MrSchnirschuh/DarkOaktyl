<?php

namespace DarkOak\Http\Controllers\Api\Client\Billing;

use DarkOak\Models\Egg;
use Illuminate\Http\Request;
use DarkOak\Models\EggVariable;
use DarkOak\Models\Billing\Product;
use DarkOak\Models\Billing\Category;
use DarkOak\Models\Billing\BillingException;
use DarkOak\Services\Billing\NodeCollectionService;
use DarkOak\Transformers\Api\Client\EggTransformer;
use DarkOak\Transformers\Api\Client\NodeTransformer;
use DarkOak\Transformers\Api\Client\ProductTransformer;
use DarkOak\Transformers\Api\Client\CategoryTransformer;
use DarkOak\Transformers\Api\Client\EggVariableTransformer;
use DarkOak\Http\Controllers\Api\Client\ClientApiController;

class StoreController extends ClientApiController
{
    public function __construct(private NodeCollectionService $nodeCollectionService)
    {
        parent::__construct();
    }

    /**
     * Returns all the categories that have been configured.
     */
    public function categories(): array
    {
        $categories = Category::where('visible', true)->get();

        if ($categories->count() == 0) {
            BillingException::create([
                'title' => 'No product categories are visible',
                'exception_type' => BillingException::TYPE_STOREFRONT,
                'description' => 'Create a category and set the visibility to true',
            ]);
        }

        return $this->transform($categories, CategoryTransformer::class);
    }

    /**
     * Returns all the products that have been configured.
     */
    public function products(Category $category): array
    {
        $products = Product::where('category_uuid', $category->uuid)->orderBy('price')->get();

        if ($products->count() == 0) {
            BillingException::create([
                'title' => 'No products in category ' . $category->name . ' are visible',
                'exception_type' => BillingException::TYPE_STOREFRONT,
                'description' => 'Go to this category and create a visible product',
            ]);
        }

        return $this->transform($products, ProductTransformer::class);
    }

    /**
     * View a specific product.
     */
    public function product(Product $product)
    {
        return $this->transform($product, ProductTransformer::class);
    }

    /**
     * Returns all the variables of an egg.
     */
    public function variables(Egg $egg): array
    {
        $variables = EggVariable::where('egg_id', $egg->id)
            ->where('user_viewable', true)
            ->get();

        return $this->transform($variables, EggVariableTransformer::class);
    }

    /**
     * Returns the eggs available for a product's nest, for products whose category
     * doesn't pin a specific egg and lets the customer choose one at checkout.
     */
    public function eggs(Product $product): array
    {
        $eggs = Egg::where('nest_id', $product->category->nest_id)->get();

        return $this->transform($eggs, EggTransformer::class);
    }

    /**
     * Returns all available nodes for deployment.
     */
    public function nodes(Request $request, Product $product): array
    {
        $nodes = $this->nodeCollectionService->handle($product);

        return $this->transform($nodes, NodeTransformer::class);
    }
}

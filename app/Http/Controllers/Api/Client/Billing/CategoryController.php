<?php

namespace DarkOak\Http\Controllers\Api\Client\Billing;

use Illuminate\Support\Facades\Cache;
use DarkOak\Models\Billing\Category;
use DarkOak\Models\Billing\BillingException;
use DarkOak\Transformers\Api\Client\CategoryTransformer;
use DarkOak\Http\Controllers\Api\Client\ClientApiController;

class CategoryController extends ClientApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns all the categories that have been configured.
     */
    public function index(): array
    {
        $categories = Cache::remember(
            'client.billing.categories.visible',
            now()->addSeconds(60),
            static fn () => Category::where('visible', true)->orderBy('name')->get(),
        );

        $storefrontMode = config('modules.billing.storefront.mode', 'products');

        if ($categories->isEmpty() && $storefrontMode === 'products') {
            BillingException::firstOrCreate(
                [
                    'title' => 'No product categories are visible',
                    'exception_type' => BillingException::TYPE_STOREFRONT,
                ],
                [
                    'description' => 'Create a category and set the visibility to true',
                ],
            );
        }

        return $this->fractal->collection($categories)
            ->transformWith(CategoryTransformer::class)
            ->toArray();
    }
}


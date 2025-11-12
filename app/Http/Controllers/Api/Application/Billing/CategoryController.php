<?php

namespace DarkOak\Http\Controllers\Api\Application\Billing;

use Ramsey\Uuid\Uuid;
use DarkOak\Models\Egg;
use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use DarkOak\Models\Billing\Category;
use Spatie\QueryBuilder\QueryBuilder;
use DarkOak\Transformers\Api\Application\CategoryTransformer;
use DarkOak\Exceptions\Http\QueryValueOutOfRangeHttpException;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\Billing\Categories\GetBillingCategoryRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Categories\GetBillingCategoriesRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Categories\StoreBillingCategoryRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Categories\DeleteBillingCategoryRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Categories\UpdateBillingCategoryRequest;

class CategoryController extends ApplicationApiController
{
    /**
     * CategoryController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all categories associated with the panel.
     */
    public function index(GetBillingCategoriesRequest $request): array
    {
        $perPage = (int) $request->query('per_page', '20');
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $categories = QueryBuilder::for(Category::query())
            ->allowedFilters(['id', 'name'])
            ->allowedSorts(['id', 'name', 'created_at', 'visible'])
            ->paginate($perPage);

        return $this->fractal->collection($categories)
            ->transformWith(CategoryTransformer::class)
            ->toArray();
    }

    /**
     * Store a new product category in the database.
     */
    public function store(StoreBillingCategoryRequest $request): JsonResponse
    {
        $egg = Egg::query()->findOrFail($request->input('eggId'));

        try {
            $category = Category::create([
                'uuid' => Uuid::uuid4()->toString(),
                'name' => $request->input('name'),
                'icon' => $request->input('icon'),
                'description' => $request->input('description'),
                'visible' => $request->input('visible'),
                'nest_id' => $egg->nest_id,
                'egg_id' => $egg->id,
            ]);
        } catch (\Exception $ex) {
            throw new \Exception('Failed to create a new product category: ' . $ex->getMessage());
        }

        Activity::event('admin:billing:categories:create')
            ->property('category', $category)
            ->description('A billing category was created')
            ->log();

        $this->clearCategoryCache($category);

        Cache::forget('application.billing.analytics');

        return $this->fractal->item($category)
            ->transformWith(CategoryTransformer::class)
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * Update an existing category.
     */
    public function update(UpdateBillingCategoryRequest $request, Category $category): Response
    {
        $egg = Egg::query()->findOrFail($request->input('eggId'));

        try {
            $category->updateOrFail([
                'name' => $request->input('name'),
                'icon' => $request->input('icon'),
                'description' => $request->input('description'),
                'visible' => $request->input('visible'),
                'nest_id' => $egg->nest_id,
                'egg_id' => $egg->id,
            ]);
        } catch (\Exception $ex) {
            throw new \Exception('Failed to update a product category: ' . $ex->getMessage());
        }

        Activity::event('admin:billing:categories:update')
            ->property('category', $category)
            ->property('new_data', $request->all())
            ->description('A billing category was updated')
            ->log();

        $this->clearCategoryCache($category);
        Cache::forget('application.billing.analytics');

        return $this->returnNoContent();
    }

    /**
     * View an existing category.
     */
    public function view(GetBillingCategoryRequest $request, Category $category): array
    {
        return $this->fractal->item($category)
            ->transformWith(CategoryTransformer::class)
            ->toArray();
    }

    /**
     * Delete a category and the products linked to it.
     */
    public function delete(DeleteBillingCategoryRequest $request, Category $category): Response
    {
        DB::transaction(function () use ($category) {
            foreach ($category->products()->get() as $product) {
                $product->forceDelete();
            }

            $category->forceDelete();
        });

        Activity::event('admin:billing:categories:delete')
            ->property('category', $category)
            ->description('A billing category was deleted')
            ->log();

        $this->clearCategoryCache($category);
        Cache::forget('application.billing.analytics');

        return $this->returnNoContent();
    }

    /**
     * Clear cached storefront category/product responses after mutations.
     */
    private function clearCategoryCache(Category $category): void
    {
        Cache::forget('client.billing.categories.visible');
        Cache::forget("client.billing.category.{$category->id}");
        Cache::forget("client.billing.category.{$category->uuid}.products");
    }
}


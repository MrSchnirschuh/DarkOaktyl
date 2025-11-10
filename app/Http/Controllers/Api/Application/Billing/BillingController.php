<?php

namespace Everest\Http\Controllers\Api\Application\Billing;

use Everest\Facades\Activity;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Everest\Models\Billing\Order;
use Everest\Models\Billing\Product;
use Everest\Models\Billing\Category;
use Everest\Contracts\Repository\SettingsRepositoryInterface;
use Everest\Http\Controllers\Api\Application\ApplicationApiController;
use Everest\Http\Requests\Api\Application\Billing\DeleteStripeKeysRequest;
use Everest\Http\Requests\Api\Application\Billing\GetBillingAnalyticsRequest;
use Everest\Http\Requests\Api\Application\Billing\UpdateBillingSettingsRequest;

class BillingController extends ApplicationApiController
{
    /**
     * BillingController constructor.
     */
    public function __construct(
        private SettingsRepositoryInterface $settings
    ) {
        parent::__construct();
    }

    /**
     * Update the billing settings for the Panel.
     *
     * @throws \Throwable
     */
    public function settings(UpdateBillingSettingsRequest $request): Response
    {
        $this->settings->set('settings::modules:billing:' . $request->input('key'), $request->input('value'));

        if (strpos($request['key'], 'keys:') !== 0) {
            Activity::event('admin:billing:update')
                ->property('settings', $request->all())
                ->description('Jexactyl billing settings were updated')
                ->log();
        }

        Cache::forget('application.billing.analytics');

        return $this->returnNoContent();
    }

    /**
     * Gather and return billing analytics.
     */
    public function analytics(GetBillingAnalyticsRequest $request): array
    {
        return Cache::remember(
            'application.billing.analytics',
            now()->addSeconds(30),
            static function (): array {
                $orders = Order::query()
                    ->select([
                        'id',
                        'name',
                        'description',
                        'total',
                        'status',
                        'product_id',
                        'type',
                        'threat_index',
                        'created_at',
                        'updated_at',
                    ])
                    ->where('created_at', '>=', now()->subMonths(6))
                    ->latest('created_at')
                    ->get()
                    ->toArray();

                $categories = Category::query()
                    ->select([
                        'id',
                        'uuid',
                        'name',
                        'icon',
                        'description',
                        'visible',
                        'nest_id',
                        'egg_id',
                        'created_at',
                        'updated_at',
                    ])
                    ->orderBy('name')
                    ->get()
                    ->toArray();

                $products = Product::query()
                    ->select([
                        'id',
                        'uuid',
                        'category_uuid',
                        'name',
                        'icon',
                        'price',
                        'description',
                        'cpu_limit',
                        'memory_limit',
                        'disk_limit',
                        'backup_limit',
                        'database_limit',
                        'allocation_limit',
                        'created_at',
                        'updated_at',
                    ])
                    ->latest('updated_at')
                    ->get()
                    ->toArray();

                return [
                    'orders' => $orders,
                    'categories' => $categories,
                    'products' => $products,
                ];
            }
        );
    }

    /**
     * Delete all Stripe API keys saved to the Panel.
     */
    public function resetKeys(DeleteStripeKeysRequest $request): Response
    {
    $this->settings->forget('settings::modules:billing:keys:publishable');
    $this->settings->forget('settings::modules:billing:keys:secret');

        Activity::event('admin:billing:reset-keys')
            ->description('Stripe API keys for billing were reset')
            ->log();

        return $this->returnNoContent();
    }
}

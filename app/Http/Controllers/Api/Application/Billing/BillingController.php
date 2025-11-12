<?php

namespace DarkOak\Http\Controllers\Api\Application\Billing;

use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use DarkOak\Models\Billing\Order;
use DarkOak\Models\Billing\Product;
use DarkOak\Models\Billing\Category;
use DarkOak\Models\Billing\ResourcePrice;
use DarkOak\Models\Billing\BillingTerm;
use DarkOak\Models\Billing\Coupon;
use DarkOak\Contracts\Repository\SettingsRepositoryInterface;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\Billing\DeleteStripeKeysRequest;
use DarkOak\Http\Requests\Api\Application\Billing\GetBillingAnalyticsRequest;
use DarkOak\Http\Requests\Api\Application\Billing\UpdateBillingSettingsRequest;

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
                ->description('DarkOaktyl billing settings were updated')
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

                $resources = ResourcePrice::query()
                    ->with('scalingRules')
                    ->select([
                        'id',
                        'uuid',
                        'resource',
                        'display_name',
                        'description',
                        'unit',
                        'base_quantity',
                        'price',
                        'currency',
                        'min_quantity',
                        'max_quantity',
                        'default_quantity',
                        'step_quantity',
                        'is_visible',
                        'is_metered',
                        'sort_order',
                        'metadata',
                        'created_at',
                        'updated_at',
                    ])
                    ->get()
                    ->map(function (ResourcePrice $resource) {
                        return array_merge($resource->toArray(), [
                            'scaling_rules' => $resource->scalingRules->map(function ($rule) {
                                return [
                                    'id' => $rule->id,
                                    'threshold' => $rule->threshold,
                                    'multiplier' => $rule->multiplier,
                                    'mode' => $rule->mode,
                                    'label' => $rule->label,
                                    'metadata' => $rule->metadata,
                                    'created_at' => optional($rule->created_at)->toAtomString(),
                                    'updated_at' => optional($rule->updated_at)->toAtomString(),
                                ];
                            })->toArray(),
                        ]);
                    })
                    ->toArray();

                $terms = BillingTerm::query()
                    ->select([
                        'id',
                        'uuid',
                        'name',
                        'slug',
                        'duration_days',
                        'multiplier',
                        'is_active',
                        'is_default',
                        'sort_order',
                        'metadata',
                        'created_at',
                        'updated_at',
                    ])
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                    ->toArray();

                $coupons = Coupon::query()
                    ->withCount('redemptions')
                    ->select([
                        'id',
                        'uuid',
                        'code',
                        'name',
                        'description',
                        'type',
                        'value',
                        'percentage',
                        'max_usages',
                        'per_user_limit',
                        'applies_to_term_id',
                        'starts_at',
                        'expires_at',
                        'is_active',
                        'metadata',
                        'created_at',
                        'updated_at',
                    ])
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function (Coupon $coupon) {
                        return array_merge($coupon->toArray(), [
                            'starts_at' => optional($coupon->starts_at)->toAtomString(),
                            'expires_at' => optional($coupon->expires_at)->toAtomString(),
                            'redemptions_count' => $coupon->redemptions_count,
                        ]);
                    })
                    ->toArray();

                return [
                    'orders' => $orders,
                    'categories' => $categories,
                    'products' => $products,
                    'resource_prices' => $resources,
                    'terms' => $terms,
                    'coupons' => $coupons,
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



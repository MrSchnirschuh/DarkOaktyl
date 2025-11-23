<?php

namespace DarkOak\Services\Billing;

use DarkOak\Models\Billing\BillingTerm;
use DarkOak\Models\Billing\Category;
use DarkOak\Models\Node;

class BuilderOrderService
{
    /**
     * @param array<string, array{quantity:int}> $quoteResources
     * @return array<string, int>
     */
    public function deriveLimits(array $quoteResources): array
    {
        $map = config('modules.billing.builder.resource_map', []);
        $limits = [];

        foreach ($quoteResources as $resourceKey => $resource) {
            $definition = $map[$resourceKey] ?? null;
            if (!$definition || empty($definition['attribute'])) {
                continue;
            }

            $attribute = $definition['attribute'];
            $limits[$attribute] = max(0, (int) ($resource['quantity'] ?? 0));
        }

        $defaults = config('modules.billing.builder.defaults', []);
        foreach ($defaults as $attribute => $value) {
            $limits[$attribute] = $limits[$attribute] ?? (int) $value;
        }

        return $limits;
    }

    /**
     * @param array<string, int> $selections
     * @param array<string, mixed> $quote
     * @param array<string, int> $limits
     * @param array<int, array{key:string,value:?string}> $variables
     * @param array<int, string> $couponCodes
     */
    public function buildMetadata(
        Category $category,
        Node $node,
        array $selections,
        array $quote,
        array $limits,
        array $variables = [],
        ?BillingTerm $term = null,
        array $couponCodes = []
    ): array {
        return [
            'category' => [
                'id' => $category->id,
                'uuid' => $category->uuid,
                'name' => $category->name,
                'nest_id' => $category->nest_id,
                'egg_id' => $category->egg_id,
            ],
            'node' => [
                'id' => $node->id,
                'name' => $node->name,
                'fqdn' => $node->fqdn,
                'type' => $node->deployable_free ? 'free' : 'paid',
            ],
            'selections' => $selections,
            'limits' => $limits,
            'variables' => array_values($variables),
            'coupons' => $couponCodes,
            'term' => $term ? [
                'id' => $term->id,
                'uuid' => $term->uuid,
                'name' => $term->name,
                'duration_days' => $term->duration_days,
                'multiplier' => $term->multiplier,
            ] : null,
            'quote' => $quote,
            'currency' => [
                'symbol' => config('modules.billing.currency.symbol'),
                'code' => config('modules.billing.currency.code'),
            ],
        ];
    }

    public function resolveTotal(array $quote): float
    {
        if (array_key_exists('total_after_discount', $quote)) {
            return (float) $quote['total_after_discount'];
        }

        return (float) ($quote['total'] ?? 0.0);
    }
}

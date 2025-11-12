<?php

namespace Everest\Services\Billing;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Everest\Models\Billing\BillingTerm;
use Everest\Models\Billing\Coupon;
use Everest\Models\Billing\ResourcePrice;
use Everest\Models\Billing\ResourceScalingRule;

class BillingPricingService
{
    /**
     * Returns all resource prices optionally filtered by visibility.
     */
    public function getResourcePrices(?bool $visibleOnly = null): Collection
    {
        $query = ResourcePrice::query()->with('scalingRules');

        if ($visibleOnly === true) {
            $query->where('is_visible', true);
        } elseif ($visibleOnly === false) {
            $query->where('is_visible', false);
        }

        return $query->get();
    }

    /**
     * Returns active billing terms ordered by the configured sort order.
     */
    public function getTerms(bool $onlyActive = true): Collection
    {
        $query = BillingTerm::query()->orderBy('sort_order');

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Calculates a quote for the provided resource selections.
     *
     * @param array $selections keyed by resource identifier => quantity
     * @param BillingTerm|null $term optional billing term to apply
     * @param array $options additional calculation options (e.g. 'snapToStep' => true)
     *
     * @return array{subtotal: float,total: float,term_multiplier: float,term?: array|null,resources: array<string,array>,options: array}
     */
    public function calculateQuote(array $selections, ?BillingTerm $term = null, array $options = []): array
    {
        $resources = $this->getResourcePrices()->keyBy('resource');
        $breakdown = [];
        $subtotal = 0.0;

        foreach ($selections as $resourceKey => $rawQuantity) {
            /** @var ResourcePrice|null $resource */
            $resource = $resources->get($resourceKey);
            if (!$resource) {
                continue;
            }

            $normalizedQuantity = $this->normalizeQuantity($resource, (int) $rawQuantity, Arr::get($options, 'snapToStep', true));
            $resourceCost = $this->calculateResourceCost($resource, $normalizedQuantity);

            $subtotal += $resourceCost['total'];

            $breakdown[$resourceKey] = [
                'resource' => $resource->resource,
                'display_name' => $resource->display_name,
                'quantity' => $normalizedQuantity,
                'unit' => $resource->unit,
                'base_quantity' => $resource->base_quantity,
                'base_price' => $resource->price,
                'applied_rules' => $resourceCost['applied_rules'],
                'total' => $resourceCost['total'],
            ];
        }

        $termData = null;
        $termMultiplier = 1.0;

        if ($term) {
            $termMultiplier = max(0.0, (float) $term->multiplier);
            $termData = [
                'id' => $term->id,
                'uuid' => $term->uuid,
                'name' => $term->name,
                'slug' => $term->slug,
                'duration_days' => $term->duration_days,
                'multiplier' => $termMultiplier,
            ];
        }

        $total = round($subtotal * $termMultiplier, 4);

        return [
            'subtotal' => round($subtotal, 4),
            'total' => $total,
            'term_multiplier' => $termMultiplier,
            'term' => $termData,
            'resources' => $breakdown,
            'options' => $options,
        ];
    }

    /**
     * Applies coupon adjustments to a calculated quote.
     * Currently supports amount and percentage coupons. Additional behaviours
     * (e.g. free resources) can be added by interpreting coupon metadata.
     */
    public function applyCoupons(array $quote, Collection|array $coupons): array
    {
        $discountTotal = 0.0;
        $coupons = $coupons instanceof Collection ? $coupons : collect($coupons);

        $now = now();

        $termId = null;
        if (!empty($quote['term']) && is_array($quote['term']) && array_key_exists('id', $quote['term'])) {
            $termId = $quote['term']['id'];
        }

        foreach ($coupons as $coupon) {
            if (!$coupon instanceof Coupon) {
                continue;
            }

            if (!$coupon->is_active) {
                continue;
            }

            if ($coupon->starts_at && $now->lt($coupon->starts_at)) {
                continue;
            }

            if ($coupon->expires_at && $now->gt($coupon->expires_at)) {
                continue;
            }

            $redemptionsCount = $coupon->redemptions_count ?? $coupon->redemptions()->count();

            if ($coupon->max_usages !== null && $redemptionsCount >= $coupon->max_usages) {
                continue;
            }

            if ($coupon->applies_to_term_id !== null && $coupon->applies_to_term_id !== $termId) {
                continue;
            }

            if ($coupon->value !== null) {
                $discountTotal += (float) $coupon->value;
            } elseif ($coupon->percentage !== null) {
                $discountTotal += $quote['total'] * ((float) $coupon->percentage);
            }
        }

        $quote['discount'] = round($discountTotal, 4);
        $quote['total_after_discount'] = max(0.0, round($quote['total'] - $quote['discount'], 4));

        return $quote;
    }

    /**
     * Ensures the provided quantity abides by minimum, maximum, and step rules.
     */
    public function normalizeQuantity(ResourcePrice $resource, int $quantity, bool $snapToStep = true): int
    {
        $quantity = max($quantity, $resource->min_quantity);

        if ($resource->max_quantity !== null) {
            $quantity = min($quantity, $resource->max_quantity);
        }

        if ($snapToStep && $resource->step_quantity > 0) {
            $step = max(1, $resource->step_quantity);
            $quantity = (int) (ceil($quantity / $step) * $step);
        }

        return $quantity;
    }

    /**
     * Calculates the total cost for the provided resource & quantity pair.
     *
     * @return array{total: float,applied_rules: array<int,array{rule: string|null,mode: string,multiplier: float}>}
     */
    protected function calculateResourceCost(ResourcePrice $resource, int $quantity): array
    {
        $baseBlocks = $resource->base_quantity > 0 ? $quantity / $resource->base_quantity : $quantity;
        $baseCost = $baseBlocks * (float) $resource->price;

        $rules = $resource->scalingRules;
        $applied = [];
        $multiplier = 1.0;

        /** @var ResourceScalingRule $rule */
        foreach ($rules as $rule) {
            if ($quantity < $rule->threshold) {
                continue;
            }

            if ($rule->mode === 'multiplier') {
                $multiplier = (float) $rule->multiplier;
            } elseif ($rule->mode === 'surcharge') {
                $baseCost += (float) $rule->multiplier;
            }

            $applied[] = [
                'rule' => $rule->label,
                'mode' => $rule->mode,
                'multiplier' => (float) $rule->multiplier,
                'threshold' => $rule->threshold,
            ];
        }

        $total = round($baseCost * $multiplier, 4);

        return [
            'total' => $total,
            'applied_rules' => $applied,
        ];
    }
}

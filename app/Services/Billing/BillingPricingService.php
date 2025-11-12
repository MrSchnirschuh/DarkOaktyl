<?php

namespace DarkOak\Services\Billing;

use Illuminate\Support\Collection;
use DarkOak\Models\Billing\BillingTerm;
use DarkOak\Models\Billing\Coupon;
use DarkOak\Models\Billing\ResourcePrice;
use DarkOak\Services\Billing\ValueObjects\QuoteOptions;
use DarkOak\Services\Billing\ValueObjects\QuoteResult;
use DarkOak\Services\Billing\ValueObjects\ResourceSelection;
use DarkOak\Services\Servers\NodeCapacityService;

class BillingPricingService
{
    public function __construct(private NodeCapacityService $nodeCapacityService)
    {
    }

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
     * @param QuoteOptions|array $options additional calculation options (e.g. 'snapToStep' => true)
     *
     * @return array{subtotal: float,total: float,term_multiplier: float,term?: array|null,resources: array<string,array>,options: array}
     */
    public function calculateQuote(array $selections, ?BillingTerm $term = null, QuoteOptions|array $options = []): array
    {
        $options = $options instanceof QuoteOptions ? $options : QuoteOptions::fromArray($options);

        /** @var Collection<string, ResourcePrice> $resources */
        $resources = $this->getResourcePrices()->keyBy('resource');
        $selectionsVO = [];
        $subtotal = 0.0;
        $capacityDemand = [];

        foreach ($selections as $resourceKey => $rawQuantity) {
            /** @var ResourcePrice|null $resource */
            $resource = $resources->get($resourceKey);
            if (!$resource) {
                continue;
            }

            $selection = ResourceSelection::fromResource($resource, (int) $rawQuantity, $options);
            $selectionsVO[$resourceKey] = $selection;
            $subtotal += $selection->total();

            foreach ($selection->capacityRequirements() as $metric => $value) {
                $capacityDemand[$metric] = ($capacityDemand[$metric] ?? 0) + $value;
            }
        }

        if ($options->shouldValidateCapacity()) {
            $this->assertNodeCapacity($options, $capacityDemand);
        }

        $termMultiplier = 1.0;
        $termData = null;

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

        $quote = new QuoteResult($selectionsVO, $subtotal, $termMultiplier, $termData, $options);

        return $quote->toArray();
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
     * @param array<string, int> $capacityDemand
     */
    private function assertNodeCapacity(QuoteOptions $options, array $capacityDemand): void
    {
        $node = $options->node();

        if (!$node) {
            return;
        }

        $memoryMb = 0;
        $diskMb = 0;

        foreach (['memory', 'memory_mb'] as $key) {
            if (isset($capacityDemand[$key])) {
                $memoryMb += (int) $capacityDemand[$key];
            }
        }

        foreach (['disk', 'disk_mb'] as $key) {
            if (isset($capacityDemand[$key])) {
                $diskMb += (int) $capacityDemand[$key];
            }
        }

        if ($memoryMb <= 0 && $diskMb <= 0) {
            return;
        }

        $this->nodeCapacityService->assertCanAllocate($node, $memoryMb, $diskMb);
    }
}


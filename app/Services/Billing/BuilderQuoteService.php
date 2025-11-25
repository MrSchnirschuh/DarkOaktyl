<?php

namespace DarkOak\Services\Billing;

use DarkOak\Http\Requests\Api\Client\Billing\Builder\BuilderQuoteRequest;
use DarkOak\Models\Billing\BillingTerm;
use DarkOak\Models\Billing\Coupon;
use DarkOak\Models\Node;

class BuilderQuoteService
{
    public function __construct(private BillingPricingService $pricing)
    {
    }

    /**
     * @return array{quote: array, coupons: array}
     */
    public function calculateFromRequest(BuilderQuoteRequest $request): array
    {
        $selections = collect($request->input('resources', []))
            ->filter(fn ($resource) => is_array($resource) && !empty($resource['resource']))
            ->mapWithKeys(fn ($resource) => [
                $resource['resource'] => (int) ($resource['quantity'] ?? 0),
            ])
            ->toArray();

        $term = null;
        if ($request->filled('term')) {
            $term = BillingTerm::query()->where('uuid', $request->input('term'))->first();
        }

        $options = [
            'snapToStep' => $request->boolean('options.snap_to_step', true),
        ];

        if ($request->has('options.validate_capacity')) {
            $options['validate_capacity'] = $request->boolean('options.validate_capacity');
        }

        $node = $this->resolveNodeFromRequest($request);
        if ($node) {
            $options['node'] = $node;
        }

        $quote = $this->pricing->calculateQuote($selections, $term, $options);
        $couponsResult = $this->applyCoupons($quote, $request->input('coupons', []));
        $deploymentType = $this->determineDeploymentType($couponsResult['quote']);

        return [
            'quote' => $couponsResult['quote'],
            'coupons' => $couponsResult['coupons'],
            'term' => $term,
            'node' => $node,
            'selections' => $selections,
            'deployment_type' => $deploymentType,
        ];
    }

    /**
     * @param array<int,string> $codes
     * @return array{quote: array, coupons: array}
     */
    private function applyCoupons(array $quote, array $codes): array
    {
        $codes = array_values(array_filter($codes));
        if (empty($codes)) {
            return ['quote' => $quote, 'coupons' => []];
        }

        $coupons = Coupon::query()
            ->withCount('redemptions')
            ->whereIn('code', $codes)
            ->get();

        if ($coupons->isEmpty()) {
            return ['quote' => $quote, 'coupons' => []];
        }

        $quote = $this->pricing->applyCoupons($quote, $coupons);

        $payload = $coupons->map(fn (Coupon $coupon) => [
            'uuid' => $coupon->uuid,
            'code' => $coupon->code,
            'name' => $coupon->name,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'percentage' => $coupon->percentage,
            'max_usages' => $coupon->max_usages,
            'per_user_limit' => $coupon->per_user_limit,
            'applies_to_term_id' => $coupon->applies_to_term_id,
            'starts_at' => optional($coupon->starts_at)->toAtomString(),
            'expires_at' => optional($coupon->expires_at)->toAtomString(),
            'is_active' => $coupon->is_active,
            'metadata' => $coupon->metadata,
            'usage_count' => $coupon->redemptions_count,
        ])->values()->toArray();

        return ['quote' => $quote, 'coupons' => $payload];
    }

    private function determineDeploymentType(array $quote): string
    {
        $resources = $quote['resources'] ?? [];
        foreach ($resources as $resource) {
            $quantity = (int) ($resource['quantity'] ?? 0);
            if ($quantity <= 0) {
                continue;
            }

            if (!empty($resource['is_metered'])) {
                return 'metered';
            }
        }

        $total = (float) ($quote['total_after_discount'] ?? $quote['total'] ?? 0.0);

        return $total <= 0.0 ? 'free' : 'paid';
    }

    private function resolveNodeFromRequest(BuilderQuoteRequest $request): ?Node
    {
        if ($request->filled('options.node_uuid')) {
            return Node::query()->where('uuid', $request->input('options.node_uuid'))->first();
        }

        if ($request->filled('options.node_id')) {
            return Node::query()->find((int) $request->input('options.node_id'));
        }

        if ($request->filled('node_id')) {
            return Node::query()->find((int) $request->input('node_id'));
        }

        return null;
    }
}

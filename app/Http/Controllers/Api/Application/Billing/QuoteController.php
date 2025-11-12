<?php

namespace DarkOak\Http\Controllers\Api\Application\Billing;

use DarkOak\Models\Billing\BillingTerm;
use DarkOak\Models\Billing\Coupon;
use DarkOak\Models\Node;
use DarkOak\Services\Billing\BillingPricingService;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\Billing\Quotes\CalculateBillingQuoteRequest;

class QuoteController extends ApplicationApiController
{
    public function __construct(private BillingPricingService $pricing)
    {
        parent::__construct();
    }

    public function calculate(CalculateBillingQuoteRequest $request): array
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

        $coupons = collect();
        if ($request->filled('coupons')) {
            $codes = $request->input('coupons', []);
            $coupons = Coupon::query()
                ->withCount('redemptions')
                ->whereIn('code', $codes)
                ->get();

            if ($coupons->isNotEmpty()) {
                $quote = $this->pricing->applyCoupons($quote, $coupons);
            }
        }

        return [
            'quote' => $quote,
            'coupons' => $coupons->map(fn (Coupon $coupon) => [
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
            ])->values()->toArray(),
        ];
    }

    private function resolveNodeFromRequest(CalculateBillingQuoteRequest $request): ?Node
    {
        if ($request->filled('options.node_uuid')) {
            return Node::query()->where('uuid', $request->input('options.node_uuid'))->first();
        }

        if ($request->filled('options.node_id')) {
            return Node::query()->find((int) $request->input('options.node_id'));
        }

        return null;
    }
}


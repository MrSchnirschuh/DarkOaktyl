<?php

namespace Everest\Http\Controllers\Api\Application\Billing;

use Everest\Models\Billing\BillingTerm;
use Everest\Models\Billing\Coupon;
use Everest\Services\Billing\BillingPricingService;
use Everest\Http\Controllers\Api\Application\ApplicationApiController;
use Everest\Http\Requests\Api\Application\Billing\Quotes\CalculateBillingQuoteRequest;

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
}

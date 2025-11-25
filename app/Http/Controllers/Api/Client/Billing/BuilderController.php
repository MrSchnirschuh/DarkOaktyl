<?php

namespace DarkOak\Http\Controllers\Api\Client\Billing;

use DarkOak\Http\Controllers\Api\Client\ClientApiController;
use DarkOak\Http\Requests\Api\Client\Billing\Builder\BuilderQuoteRequest;
use DarkOak\Models\Billing\BillingTerm;
use DarkOak\Services\Billing\BillingPricingService;
use DarkOak\Services\Billing\BuilderQuoteService;

class BuilderController extends ClientApiController
{
    public function __construct(private BillingPricingService $pricing, private BuilderQuoteService $quotes)
    {
        parent::__construct();
    }

    public function resources(): array
    {
        $resources = $this->pricing->getResourcePrices(true)->map(function ($resource) {
            return [
                'uuid' => $resource->uuid,
                'resource' => $resource->resource,
                'display_name' => $resource->display_name,
                'description' => $resource->description,
                'unit' => $resource->unit,
                'base_quantity' => $resource->base_quantity,
                'price' => $resource->price,
                'currency' => $resource->currency,
                'min_quantity' => $resource->min_quantity,
                'max_quantity' => $resource->max_quantity,
                'default_quantity' => $resource->default_quantity,
                'step_quantity' => $resource->step_quantity,
                'is_metered' => $resource->is_metered,
                'sort_order' => $resource->sort_order,
                'scaling_rules' => $resource->scalingRules->map(function ($rule) {
                    return [
                        'id' => $rule->id,
                        'threshold' => $rule->threshold,
                        'mode' => $rule->mode,
                        'multiplier' => $rule->multiplier,
                        'label' => $rule->label,
                        'metadata' => $rule->metadata,
                    ];
                })->values()->toArray(),
            ];
        })->values();

        return ['data' => $resources];
    }

    public function terms(): array
    {
        $terms = $this->pricing->getTerms()->map(function (BillingTerm $term) {
            return [
                'id' => $term->id,
                'uuid' => $term->uuid,
                'name' => $term->name,
                'slug' => $term->slug,
                'duration_days' => $term->duration_days,
                'multiplier' => $term->multiplier,
                'is_default' => $term->is_default,
            ];
        })->values();

        return ['data' => $terms];
    }

    public function quote(BuilderQuoteRequest $request): array
    {
        $result = $this->quotes->calculateFromRequest($request);

        return [
            'quote' => $result['quote'],
            'coupons' => $result['coupons'],
            'deployment_type' => $result['deployment_type'] ?? 'paid',
        ];
    }
}

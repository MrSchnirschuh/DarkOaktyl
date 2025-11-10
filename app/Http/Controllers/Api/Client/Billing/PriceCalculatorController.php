<?php

namespace Everest\Http\Controllers\Api\Client\Billing;

use Illuminate\Http\JsonResponse;
use Everest\Models\Billing\PricingConfiguration;
use Everest\Models\Billing\PricingDuration;
use Everest\Http\Controllers\Api\Client\ClientApiController;
use Everest\Http\Requests\Api\Client\Billing\CalculatePriceRequest;

class PriceCalculatorController extends ClientApiController
{
    /**
     * Calculate the price for a given configuration of resources.
     */
    public function calculate(CalculatePriceRequest $request): JsonResponse
    {
        $pricingConfig = PricingConfiguration::findOrFail($request->input('pricing_configuration_id'));

        if (!$pricingConfig->enabled) {
            return response()->json([
                'error' => 'This pricing configuration is not currently available.',
            ], 400);
        }

        $basePrice = $pricingConfig->calculatePrice(
            $request->integer('cpu'),
            $request->integer('memory'),
            $request->integer('disk'),
            $request->integer('backups'),
            $request->integer('databases'),
            $request->integer('allocations')
        );

        $finalPrice = $basePrice;
        $durationFactor = 1.0;

        // Apply duration pricing if specified
        if ($request->has('duration_days')) {
            $duration = PricingDuration::where('pricing_configuration_id', $pricingConfig->id)
                ->where('duration_days', $request->integer('duration_days'))
                ->where('enabled', true)
                ->first();

            if ($duration) {
                $durationFactor = $duration->price_factor;
                $finalPrice = round($basePrice * $durationFactor, 2);
            }
        }

        return response()->json([
            'base_price' => $basePrice,
            'duration_factor' => $durationFactor,
            'final_price' => $finalPrice,
            'currency' => config('billing.currency', 'USD'),
        ]);
    }
}

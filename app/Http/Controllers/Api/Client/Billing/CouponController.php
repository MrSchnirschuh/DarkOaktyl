<?php

namespace Everest\Http\Controllers\Api\Client\Billing;

use Illuminate\Http\JsonResponse;
use Everest\Models\Billing\Coupon;
use Illuminate\Support\Facades\Validator;
use Everest\Http\Controllers\Api\Client\ClientApiController;
use Everest\Http\Requests\Api\Client\ClientApiRequest;

class CouponController extends ClientApiController
{
    /**
     * Validate a coupon code and return discount information.
     */
    public function validate(ClientApiRequest $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'total' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $coupon = Coupon::where('code', $request->input('code'))->first();

        if (!$coupon) {
            return response()->json([
                'valid' => false,
                'error' => 'Coupon not found',
            ], 404);
        }

        if (!$coupon->isValid()) {
            return response()->json([
                'valid' => false,
                'error' => 'Coupon is not valid or has expired',
            ], 400);
        }

        $total = (float) $request->input('total');
        $discount = $coupon->calculateDiscount($total);
        $newTotal = max(0, $total - $discount);

        return response()->json([
            'valid' => true,
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
            ],
            'discount_amount' => $discount,
            'original_total' => $total,
            'new_total' => $newTotal,
        ]);
    }
}

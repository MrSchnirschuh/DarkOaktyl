<?php

namespace Everest\Http\Controllers\Api\Application\Billing;

use Everest\Facades\Activity;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Everest\Models\Billing\Coupon;
use Spatie\QueryBuilder\QueryBuilder;
use Everest\Transformers\Api\Application\CouponTransformer;
use Everest\Exceptions\Http\QueryValueOutOfRangeHttpException;
use Everest\Http\Controllers\Api\Application\ApplicationApiController;
use Everest\Http\Requests\Api\Application\Billing\Coupons\GetBillingCouponRequest;
use Everest\Http\Requests\Api\Application\Billing\Coupons\GetBillingCouponsRequest;
use Everest\Http\Requests\Api\Application\Billing\Coupons\StoreBillingCouponRequest;
use Everest\Http\Requests\Api\Application\Billing\Coupons\DeleteBillingCouponRequest;
use Everest\Http\Requests\Api\Application\Billing\Coupons\UpdateBillingCouponRequest;

class CouponController extends ApplicationApiController
{
    /**
     * CouponController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all coupons.
     */
    public function index(GetBillingCouponsRequest $request): array
    {
        $perPage = (int) $request->query('per_page', '20');
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $coupons = QueryBuilder::for(Coupon::query())
            ->allowedFilters(['code', 'discount_type', 'is_active'])
            ->allowedSorts(['id', 'code', 'uses', 'created_at'])
            ->paginate($perPage);

        return $this->fractal->collection($coupons)
            ->transformWith(CouponTransformer::class)
            ->toArray();
    }

    /**
     * Store a new coupon in the database.
     */
    public function store(StoreBillingCouponRequest $request): JsonResponse
    {
        try {
            $coupon = Coupon::create([
                'code' => $request->input('code'),
                'description' => $request->input('description'),
                'discount_type' => $request->input('discount_type'),
                'discount_value' => (float) $request->input('discount_value'),
                'max_uses' => $request->input('max_uses'),
                'expires_at' => $request->input('expires_at'),
                'is_active' => $request->input('is_active', true),
            ]);
        } catch (\Exception $ex) {
            throw new \Exception('Failed to create a new coupon: ' . $ex->getMessage());
        }

        Activity::event('admin:billing:coupons:create')
            ->property('coupon', $coupon)
            ->description('A new billing coupon was created')
            ->log();

        return $this->fractal->item($coupon)
            ->transformWith(CouponTransformer::class)
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * Update an existing coupon.
     */
    public function update(UpdateBillingCouponRequest $request, int $couponId): Response
    {
        $coupon = Coupon::findOrFail($couponId);

        try {
            $coupon->update([
                'description' => $request->input('description', $coupon->description),
                'discount_type' => $request->input('discount_type', $coupon->discount_type),
                'discount_value' => $request->input('discount_value', $coupon->discount_value),
                'max_uses' => $request->input('max_uses', $coupon->max_uses),
                'expires_at' => $request->input('expires_at', $coupon->expires_at),
                'is_active' => $request->input('is_active', $coupon->is_active),
            ]);
        } catch (\Exception $ex) {
            throw new \Exception('Failed to update coupon: ' . $ex->getMessage());
        }

        Activity::event('admin:billing:coupons:update')
            ->property('coupon', $coupon)
            ->property('new_data', $request->all())
            ->description('A billing coupon has been updated')
            ->log();

        return $this->returnNoContent();
    }

    /**
     * View an existing coupon.
     */
    public function view(GetBillingCouponRequest $request, int $couponId): array
    {
        $coupon = Coupon::findOrFail($couponId);

        return $this->fractal->item($coupon)
            ->transformWith(CouponTransformer::class)
            ->toArray();
    }

    /**
     * Delete a coupon.
     */
    public function delete(DeleteBillingCouponRequest $request, int $couponId): Response
    {
        $coupon = Coupon::findOrFail($couponId);

        $coupon->delete();

        Activity::event('admin:billing:coupons:delete')
            ->property('coupon', $coupon)
            ->description('A billing coupon has been deleted')
            ->log();

        return $this->returnNoContent();
    }
}

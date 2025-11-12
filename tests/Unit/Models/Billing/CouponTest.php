<?php

namespace Everest\Tests\Unit\Models\Billing;

use Carbon\Carbon;
use Everest\Models\Billing\Coupon;
use Everest\Tests\TestCase;

class CouponTest extends TestCase
{
    /**
     * Test that a coupon is valid when active and not expired.
     */
    public function testCouponIsValidWhenActiveAndNotExpired()
    {
        $coupon = new Coupon([
            'code' => 'TEST2025',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        $this->assertTrue($coupon->isValid());
    }

    /**
     * Test that a coupon is invalid when not active.
     */
    public function testCouponIsInvalidWhenNotActive()
    {
        $coupon = new Coupon([
            'code' => 'INACTIVE',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => false,
        ]);

        $this->assertFalse($coupon->isValid());
    }

    /**
     * Test that a coupon is invalid when expired.
     */
    public function testCouponIsInvalidWhenExpired()
    {
        $coupon = new Coupon([
            'code' => 'EXPIRED',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'expires_at' => Carbon::now()->subDays(1),
        ]);

        $this->assertFalse($coupon->isValid());
    }

    /**
     * Test that a coupon is invalid when max uses reached.
     */
    public function testCouponIsInvalidWhenMaxUsesReached()
    {
        $coupon = new Coupon([
            'code' => 'MAXED',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'max_uses' => 5,
            'uses' => 5,
        ]);

        $this->assertFalse($coupon->isValid());
    }

    /**
     * Test percentage discount calculation.
     */
    public function testPercentageDiscountCalculation()
    {
        $coupon = new Coupon([
            'code' => 'PERCENT10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
        ]);

        $discount = $coupon->calculateDiscount(100);
        $this->assertEquals(10, $discount);
    }

    /**
     * Test fixed discount calculation.
     */
    public function testFixedDiscountCalculation()
    {
        $coupon = new Coupon([
            'code' => 'FIXED5',
            'discount_type' => 'fixed',
            'discount_value' => 5,
        ]);

        $discount = $coupon->calculateDiscount(100);
        $this->assertEquals(5, $discount);
    }

    /**
     * Test that discount cannot exceed total.
     */
    public function testDiscountCannotExceedTotal()
    {
        $coupon = new Coupon([
            'code' => 'BIG',
            'discount_type' => 'fixed',
            'discount_value' => 200,
        ]);

        $discount = $coupon->calculateDiscount(50);
        $this->assertEquals(50, $discount);
    }
}

<?php

namespace Everest\Models\Billing;

use Everest\Models\Billing\Order;
use Everest\Models\Model;
use Everest\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $coupon_id
 * @property int|null $user_id
 * @property int|null $order_id
 * @property float $amount
 * @property array|null $metadata
 * @property \Carbon\Carbon $redeemed_at
 */
class CouponRedemption extends Model
{
    public const RESOURCE_NAME = 'coupon_redemption';

    protected $table = 'coupon_redemptions';

    protected $fillable = [
        'coupon_id',
        'user_id',
        'order_id',
        'amount',
        'metadata',
        'redeemed_at',
    ];

    protected $casts = [
        'coupon_id' => 'integer',
        'user_id' => 'integer',
        'order_id' => 'integer',
        'amount' => 'float',
        'metadata' => 'array',
        'redeemed_at' => 'datetime',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}

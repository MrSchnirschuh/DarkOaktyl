<?php

namespace Everest\Models\Billing;

use Carbon\Carbon;
use Everest\Models\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $description
 * @property string $discount_type
 * @property float $discount_value
 * @property int|null $max_uses
 * @property int $uses
 * @property Carbon|null $expires_at
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Coupon extends Model
{
    public const DISCOUNT_TYPE_PERCENTAGE = 'percentage';
    public const DISCOUNT_TYPE_FIXED = 'fixed';

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'coupon';

    /**
     * The table associated with the model.
     */
    protected $table = 'coupons';

    /**
     * Fields that are mass assignable.
     */
    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'max_uses',
        'uses',
        'expires_at',
        'is_active',
    ];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'discount_value' => 'float',
        'max_uses' => 'integer',
        'uses' => 'integer',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public static array $validationRules = [
        'code' => 'required|string|min:3|max:50|unique:coupons,code',
        'description' => 'nullable|string|max:500',
        'discount_type' => 'required|in:percentage,fixed',
        'discount_value' => 'required|numeric|min:0',
        'max_uses' => 'nullable|integer|min:1',
        'expires_at' => 'nullable|date|after:now',
        'is_active' => 'boolean',
    ];

    /**
     * Check if the coupon is valid for use.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->uses >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the discount amount for a given total.
     */
    public function calculateDiscount(float $total): float
    {
        if ($this->discount_type === self::DISCOUNT_TYPE_PERCENTAGE) {
            $discount = ($total * $this->discount_value) / 100;
            return min($discount, $total);
        }

        return min($this->discount_value, $total);
    }

    /**
     * Increment the usage count of this coupon.
     */
    public function incrementUses(): void
    {
        $this->increment('uses');
    }

    /**
     * Get all orders that used this coupon.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}

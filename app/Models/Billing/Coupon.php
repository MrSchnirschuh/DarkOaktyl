<?php

namespace Everest\Models\Billing;

use Everest\Models\Model;
use Everest\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property float|null $value
 * @property float|null $percentage
 * @property int|null $max_usages
 * @property int|null $per_user_limit
 * @property int|null $applies_to_term_id
 * @property array|null $metadata
 * @property bool $is_active
 * @property \Carbon\Carbon|null $starts_at
 * @property \Carbon\Carbon|null $expires_at
 */
class Coupon extends Model
{
    public const RESOURCE_NAME = 'coupon';

    protected $table = 'coupons';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'description',
        'type',
        'value',
        'percentage',
        'max_usages',
        'per_user_limit',
        'applies_to_term_id',
        'created_by_id',
        'updated_by_id',
        'starts_at',
        'expires_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'value' => 'float',
        'percentage' => 'float',
        'max_usages' => 'integer',
        'per_user_limit' => 'integer',
        'applies_to_term_id' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'bool',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if (empty($model->code)) {
                $model->code = strtoupper(Str::random(12));
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(BillingTerm::class, 'applies_to_term_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class, 'coupon_id');
    }
}

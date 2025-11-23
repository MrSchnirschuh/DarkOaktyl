<?php

namespace DarkOak\Models\Billing;

use DarkOak\Models\Model;

/**
 * @property int $id
 * @property string $name
 * @property int $user_id
 * @property string $description
 * @property float $total
 * @property string $status
 * @property int|null $product_id
 * @property int|null $billing_term_id
 * @property int|null $node_id
 * @property string $storefront
 * @property array|null $builder_metadata
 * @property string $type
 * @property int $threat_index
 * @property string $payment_intent_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Order extends Model
{
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSED = 'processed';

    public const TYPE_NEW = 'new';
    public const TYPE_UPG = 'upg';
    public const TYPE_REN = 'ren';

    public const STOREFRONT_PRODUCTS = 'products';
    public const STOREFRONT_BUILDER = 'builder';

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'order';

    /**
     * The table associated with the model.
     */
    protected $table = 'orders';

    /**
     * Fields that are mass assignable.
     */
    protected $fillable = [
        'name', 'user_id', 'description', 'payment_intent_id',
        'total', 'status', 'product_id', 'type', 'threat_index',
        'billing_term_id', 'node_id', 'storefront', 'builder_metadata',
    ];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'user_id' => 'int',
        'total' => 'float',
        'product_id' => 'int',
        'billing_term_id' => 'int',
        'node_id' => 'int',
        'threat_index' => 'int',
        'builder_metadata' => 'array',
    ];

    public static array $validationRules = [
        'name' => 'string|required|min:3',
        'user_id' => 'required|exists:users,id',
        'description' => 'required|string|min:3',
        'total' => 'required|min:0',
        'status' => 'required|in:expired,pending,failed,processed',
        'product_id' => 'nullable|exists:products,id',
        'billing_term_id' => 'nullable|exists:billing_terms,id',
        'node_id' => 'nullable|exists:nodes,id',
        'storefront' => 'required|string|in:products,builder',
        'type' => 'required|in:new,upg,ren',
        'threat_index' => 'nullable|int|min:-1|max:100',
        'payment_intent_id' => 'required|string|unique:orders,payment_intent_id',
    ];
}


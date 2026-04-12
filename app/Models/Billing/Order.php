<?php

namespace DarkOak\Models\Billing;

use DarkOak\Models\Model;
use DarkOak\Models\User;
use DarkOak\Models\Server;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property int $user_id
 * @property string $description
 * @property float $total
 * @property string $status
 * @property int $product_id
 * @property int|null $server_id
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
        'total', 'status', 'product_id', 'type', 'threat_index', 'server_id',
    ];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'user_id' => 'int',
        'total' => 'float',
        'product_id' => 'int',
        'threat_index' => 'int',
        'server_id' => 'int',
    ];

    public static array $validationRules = [
        'name' => 'string|required|min:3',
        'user_id' => 'required|exists:users,id',
        'description' => 'required|string|min:3',
        'total' => 'required|min:0',
        'status' => 'required|in:expired,pending,failed,processed',
        'product_id' => 'exists:products,id',
        'type' => 'required|in:new,upg,ren',
        'threat_index' => 'nullable|int|min:-1|max:100',
        'payment_intent_id' => 'required|string|unique:orders,payment_intent_id',
        'server_id' => 'nullable|exists:servers,id',
    ];

    /**
     * Gets the user who this order is assigned to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Gets the server associated with this order.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    /**
     * Gets the product which this order is assigned to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Return whether a payment must be collected for this order.
     */
    public function requiresPayment(): bool
    {
        return $this->total > 0.0;
    }

    /**
     * Return whether this order has already been processed.
     */
    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    /**
     * Return whether this order is a renewal.
     */
    public function isRenewal(): bool
    {
        return $this->type === self::TYPE_REN;
    }

    /**
     * Assign a server ID to this model.
     */
    public function assignServer(Server $server): void
    {
        $this->server()->associate($server);
        $this->save();
    }

    /**
     * A helper function to set the order status.
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->save();
    }
}

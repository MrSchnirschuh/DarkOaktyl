<?php

namespace DarkOak\Models\Billing;

use Carbon\Carbon;
use DarkOak\Models\Model;
use DarkOak\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property float $amount
 * @property float $balance_before
 * @property float $balance_after
 * @property string|null $description
 * @property string $transactionable_type
 * @property int $transactionable_id
 * @property string|null $reference_id
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 */
class CreditTransaction extends Model
{
    public const RESOURCE_NAME = 'credit_transaction';

    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT = 'debit';
    public const TYPE_REFUND = 'refund';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $table = 'credit_transactions';

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'transactionable_type',
        'transactionable_id',
        'reference_id',
        'metadata',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'amount' => 'float',
        'balance_before' => 'float',
        'balance_after' => 'float',
        'metadata' => 'array',
    ];

    public static array $validationRules = [
        'user_id' => 'required|integer|exists:users,id',
        'type' => 'required|in:credit,debit,refund,adjustment',
        'amount' => 'required|numeric|min:0',
        'balance_before' => 'required|numeric|min:0',
        'balance_after' => 'required|numeric|min:0',
        'description' => 'nullable|string',
        'transactionable_type' => 'required|string',
        'transactionable_id' => 'required|integer',
        'reference_id' => 'nullable|string',
        'metadata' => 'nullable|array',
    ];

    /**
     * Get the user that owns this transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transactionable entity.
     */
    public function transactionable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the credit balance for this user.
     */
    public function creditBalance(): BelongsTo
    {
        return $this->belongsTo(CreditBalance::class, 'user_id', 'user_id');
    }

    /**
     * Create a new transaction.
     */
    public static function createTransaction(
        int $userId,
        string $type,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        string $description = null,
        $transactionable = null,
        string $referenceId = null,
        array $metadata = []
    ): self {
        $data = [
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $description,
            'reference_id' => $referenceId,
            'metadata' => $metadata,
        ];

        if ($transactionable) {
            $data['transactionable_type'] = get_class($transactionable);
            $data['transactionable_id'] = $transactionable->id;
        }

        return self::create($data);
    }
}
<?php

namespace DarkOak\Models\Billing;

use Carbon\Carbon;
use DarkOak\Models\Model;
use DarkOak\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property float $balance
 * @property float $reserved_balance
 * @property float $low_balance_threshold
 * @property bool $low_balance_warning_sent
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read CreditTransaction[] $transactions
 */
class CreditBalance extends Model
{
    public const RESOURCE_NAME = 'credit_balance';

    protected $table = 'credit_balances';

    protected $fillable = [
        'user_id',
        'balance',
        'reserved_balance',
        'low_balance_threshold',
        'low_balance_warning_sent',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'balance' => 'float',
        'reserved_balance' => 'float',
        'low_balance_threshold' => 'float',
        'low_balance_warning_sent' => 'boolean',
    ];

    public static array $validationRules = [
        'user_id' => 'required|integer|exists:users,id',
        'balance' => 'required|numeric|min:0',
        'reserved_balance' => 'required|numeric|min:0',
        'low_balance_threshold' => 'required|numeric|min:0',
        'low_balance_warning_sent' => 'boolean',
    ];

    /**
     * Get the user that owns this credit balance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the credit transactions for this user.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class, 'user_id', 'user_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the available balance (balance - reserved).
     */
    public function availableBalance(): float
    {
        return max(0, $this->balance - $this->reserved_balance);
    }

    /**
     * Check if the balance is low.
     */
    public function isLowBalance(): bool
    {
        return $this->balance <= $this->low_balance_threshold;
    }

    /**
     * Check if the balance is sufficient for an amount.
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->availableBalance() >= $amount;
    }

    /**
     * Add credit to the balance.
     */
    public function addCredit(float $amount, ?string $description = null, $transactionable = null, ?string $referenceId = null, array $metadata = []): CreditTransaction
    {
        $balanceBefore = $this->balance;
        $this->balance += $amount;
        $this->save();

        return CreditTransaction::createTransaction(
            $this->user_id,
            CreditTransaction::TYPE_CREDIT,
            $amount,
            $balanceBefore,
            $this->balance,
            $description,
            $transactionable,
            $referenceId,
            $metadata
        );
    }

    /**
     * Debit credit from the balance.
     */
    public function debitCredit(float $amount, ?string $description = null, $transactionable = null, ?string $referenceId = null, array $metadata = []): ?CreditTransaction
    {
        if (!$this->hasSufficientBalance($amount)) {
            return null;
        }

        $balanceBefore = $this->balance;
        $this->balance -= $amount;
        $this->save();

        return CreditTransaction::createTransaction(
            $this->user_id,
            CreditTransaction::TYPE_DEBIT,
            $amount,
            $balanceBefore,
            $this->balance,
            $description,
            $transactionable,
            $referenceId,
            $metadata
        );
    }

    /**
     * Reserve an amount for pending billing.
     */
    public function reserveBalance(float $amount): bool
    {
        if (!$this->hasSufficientBalance($amount)) {
            return false;
        }

        $this->reserved_balance += $amount;
        $this->save();

        return true;
    }

    /**
     * Release reserved balance.
     */
    public function releaseReservedBalance(float $amount): void
    {
        $this->reserved_balance = max(0, $this->reserved_balance - $amount);
        $this->save();
    }

    /**
     * Mark low balance warning as sent.
     */
    public function markWarningSent(): void
    {
        $this->low_balance_warning_sent = true;
        $this->save();
    }

    /**
     * Reset low balance warning.
     */
    public function resetWarningSent(): void
    {
        $this->low_balance_warning_sent = false;
        $this->save();
    }

    /**
     * Get or create credit balance for a user.
     */
    public static function forUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0, 'reserved_balance' => 0]
        );
    }
}
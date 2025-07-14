<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet\Models;

use App\Infrastructure\Order\Models\EloquentOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EloquentTransaction extends Model
{
    use HasFactory;

    protected $table = 'wallet_transactions';

    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'balance_after',
        'reference',
        'description',
        'status',
        'metadata',
        'related_transaction_id',
        'order_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the wallet that owns this transaction
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(EloquentWallet::class, 'wallet_id');
    }

    /**
     * Get the related transaction
     */
    public function relatedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_transaction_id');
    }

    /**
     * Get the order associated with this transaction
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(EloquentOrder::class, 'order_id');
    }

    /**
     * Scope for specific transaction type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for specific status
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for credit transactions
     */
    public function scopeCredits($query)
    {
        return $query->whereIn('type', [
            'credit',
            'transfer_in',
            'bet_won',
            'bet_refund',
            'commission_earned',
            'bonus_added',
            'deposit',
        ]);
    }

    /**
     * Scope for debit transactions
     */
    public function scopeDebits($query)
    {
        return $query->whereIn('type', [
            'debit',
            'transfer_out',
            'bet_placed',
            'commission_shared',
            'bonus_used',
            'withdrawal',
            'fee',
        ]);
    }

    /**
     * Scope for transactions by reference
     */
    public function scopeByReference($query, string $reference)
    {
        return $query->where('reference', $reference);
    }

    /**
     * Scope for transactions within date range
     */
    public function scopeWithinDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for transactions with amount above threshold
     */
    public function scopeWithAmountAbove($query, float $threshold)
    {
        return $query->where('amount', '>', $threshold);
    }

    /**
     * Scope for transactions with amount below threshold
     */
    public function scopeWithAmountBelow($query, float $threshold)
    {
        return $query->where('amount', '<', $threshold);
    }

    /**
     * Check if transaction is a credit
     */
    public function isCredit(): bool
    {
        return in_array($this->type, [
            'credit',
            'transfer_in',
            'bet_won',
            'bet_refund',
            'commission_earned',
            'bonus_added',
            'deposit',
        ]);
    }

    /**
     * Check if transaction is a debit
     */
    public function isDebit(): bool
    {
        return in_array($this->type, [
            'debit',
            'transfer_out',
            'bet_placed',
            'commission_shared',
            'bonus_used',
            'withdrawal',
            'fee',
        ]);
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        $currency = $this->wallet->currency ?? 'USD';

        return number_format($this->amount, 2).' '.$currency;
    }

    /**
     * Get transaction type display name
     */
    public function getTypeDisplayNameAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->type));
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return ucwords($this->status);
    }
}

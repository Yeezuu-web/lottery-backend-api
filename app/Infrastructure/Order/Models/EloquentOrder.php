<?php

declare(strict_types=1);

namespace App\Infrastructure\Order\Models;

use App\Infrastructure\Agent\Models\EloquentAgent;
use App\Infrastructure\Wallet\Models\EloquentTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class EloquentOrder extends Model
{
    use HasFactory;

    protected $table = 'ca_orders';

    protected $fillable = [
        'agent_id',
        'order_number',
        'group_id',
        'bet_data',
        'expanded_numbers',
        'channel_weights',
        'total_amount',
        'currency',
        'status',
        'is_printed',
        'printed_at',
        'placed_at',
    ];

    protected $casts = [
        'bet_data' => 'array',
        'expanded_numbers' => 'array',
        'channel_weights' => 'array',
        'total_amount' => 'decimal:2',
        'is_printed' => 'boolean',
        'printed_at' => 'datetime',
        'placed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'currency' => 'KHR',
        'status' => 'pending',
        'is_printed' => false,
    ];

    /**
     * Get the agent that placed this order
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(EloquentAgent::class, 'agent_id');
    }

    /**
     * Get all transactions related to this order
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(EloquentTransaction::class, 'order_id');
    }

    /**
     * Get bet placed transactions for this order
     */
    public function betTransactions(): HasMany
    {
        return $this->hasMany(EloquentTransaction::class, 'order_id')
            ->where('type', 'bet_placed');
    }

    /**
     * Get winning transactions for this order
     */
    public function winningTransactions(): HasMany
    {
        return $this->hasMany(EloquentTransaction::class, 'order_id')
            ->where('type', 'bet_won');
    }

    /**
     * Get refund transactions for this order
     */
    public function refundTransactions(): HasMany
    {
        return $this->hasMany(EloquentTransaction::class, 'order_id')
            ->where('type', 'bet_refund');
    }

    /**
     * Scope for orders with specific status
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending orders
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for accepted orders
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope for won orders
     */
    public function scopeWon($query)
    {
        return $query->where('status', 'won');
    }

    /**
     * Scope for lost orders
     */
    public function scopeLost($query)
    {
        return $query->where('status', 'lost');
    }

    /**
     * Scope for cancelled orders
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope for printed orders
     */
    public function scopePrinted($query)
    {
        return $query->where('is_printed', true);
    }

    /**
     * Scope for unprinted orders
     */
    public function scopeUnprinted($query)
    {
        return $query->where('is_printed', false);
    }

    /**
     * Scope for orders by bet type
     */
    public function scopeByBetType($query, string $betType)
    {
        return $query->whereJsonContains('bet_data->type', $betType);
    }

    /**
     * Scope for orders by period
     */
    public function scopeByPeriod($query, string $period)
    {
        return $query->whereJsonContains('bet_data->period', $period);
    }

    /**
     * Scope for orders within date range
     */
    public function scopeWithinDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('placed_at', [$startDate, $endDate]);
    }

    /**
     * Scope for orders with amount above threshold
     */
    public function scopeWithAmountAbove($query, float $threshold)
    {
        return $query->where('total_amount', '>', $threshold);
    }

    /**
     * Scope for orders with amount below threshold
     */
    public function scopeWithAmountBelow($query, float $threshold)
    {
        return $query->where('total_amount', '<', $threshold);
    }

    /**
     * Scope for orders by group
     */
    public function scopeByGroup($query, string $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    /**
     * Check if order is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if order is accepted
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if order is won
     */
    public function isWon(): bool
    {
        return $this->status === 'won';
    }

    /**
     * Check if order is lost
     */
    public function isLost(): bool
    {
        return $this->status === 'lost';
    }

    /**
     * Check if order is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if order is printed
     */
    public function isPrinted(): bool
    {
        return $this->is_printed;
    }

    /**
     * Get bet type from bet data
     */
    public function getBetTypeAttribute(): ?string
    {
        return $this->bet_data['type'] ?? null;
    }

    /**
     * Get period from bet data
     */
    public function getPeriodAttribute(): ?string
    {
        return $this->bet_data['period'] ?? null;
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->total_amount, 2).' '.$this->currency;
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Get order display name
     */
    public function getDisplayNameAttribute(): string
    {
        return 'Order #'.$this->order_number;
    }
}

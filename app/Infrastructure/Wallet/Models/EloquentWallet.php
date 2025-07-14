<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet\Models;

use App\Infrastructure\Agent\Models\EloquentAgent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class EloquentWallet extends Model
{
    use HasFactory;

    protected $table = 'agent_multi_wallets';

    protected $fillable = [
        'owner_id',
        'wallet_type',
        'balance',
        'locked_balance',
        'currency',
        'is_active',
        'last_transaction_at',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'locked_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'last_transaction_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the agent that owns this wallet
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(EloquentAgent::class, 'owner_id');
    }

    /**
     * Get all transactions for this wallet
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(EloquentTransaction::class, 'wallet_id');
    }

    /**
     * Get recent transactions
     */
    public function recentTransactions(int $limit = 10): HasMany
    {
        return $this->hasMany(EloquentTransaction::class, 'wallet_id')
            ->orderBy('created_at', 'desc')
            ->limit($limit);
    }

    /**
     * Get pending transactions
     */
    public function pendingTransactions(): HasMany
    {
        return $this->hasMany(EloquentTransaction::class, 'wallet_id')
            ->where('status', 'pending');
    }

    /**
     * Get completed transactions
     */
    public function completedTransactions(): HasMany
    {
        return $this->hasMany(EloquentTransaction::class, 'wallet_id')
            ->where('status', 'completed');
    }

    /**
     * Scope for active wallets
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific wallet type
     */
    public function scopeOfType($query, string $walletType)
    {
        return $query->where('wallet_type', $walletType);
    }

    /**
     * Scope for specific currency
     */
    public function scopeOfCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Scope for wallets with balance above threshold
     */
    public function scopeWithBalanceAbove($query, float $threshold)
    {
        return $query->where('balance', '>', $threshold);
    }

    /**
     * Scope for wallets with balance below threshold
     */
    public function scopeWithBalanceBelow($query, float $threshold)
    {
        return $query->where('balance', '<', $threshold);
    }

    /**
     * Check if wallet has enough balance
     */
    public function hasEnoughBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Get available balance (balance - locked_balance)
     */
    public function getAvailableBalanceAttribute(): float
    {
        return $this->balance - $this->locked_balance;
    }

    /**
     * Check if wallet is locked
     */
    public function isLocked(): bool
    {
        return $this->locked_balance > 0;
    }

    /**
     * Get wallet display name
     */
    public function getDisplayNameAttribute(): string
    {
        return sprintf('%s Wallet - %s', $this->wallet_type, $this->currency);
    }
}

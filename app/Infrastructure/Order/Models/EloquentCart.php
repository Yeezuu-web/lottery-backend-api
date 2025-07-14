<?php

namespace App\Infrastructure\Order\Models;

use App\Infrastructure\Agent\Models\EloquentAgent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentCart extends Model
{
    use HasFactory;

    protected $table = 'ca_cart';

    protected $fillable = [
        'agent_id',
        'bet_data',
        'expanded_numbers',
        'channel_weights',
        'total_amount',
        'currency',
        'status',
    ];

    protected $casts = [
        'bet_data' => 'array',
        'expanded_numbers' => 'array',
        'channel_weights' => 'array',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the agent that owns this cart item
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(EloquentAgent::class, 'agent_id');
    }

    /**
     * Scope for cart items with specific status
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for active cart items
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for submitted cart items
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Scope for expired cart items
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope for cart items by bet type
     */
    public function scopeByBetType($query, string $betType)
    {
        return $query->whereJsonContains('bet_data->type', $betType);
    }

    /**
     * Scope for cart items by period
     */
    public function scopeByPeriod($query, string $period)
    {
        return $query->whereJsonContains('bet_data->period', $period);
    }

    /**
     * Scope for cart items within date range
     */
    public function scopeWithinDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for cart items with amount above threshold
     */
    public function scopeWithAmountAbove($query, float $threshold)
    {
        return $query->where('total_amount', '>', $threshold);
    }

    /**
     * Scope for cart items with amount below threshold
     */
    public function scopeWithAmountBelow($query, float $threshold)
    {
        return $query->where('total_amount', '<', $threshold);
    }

    /**
     * Scope for recent cart items
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for old cart items
     */
    public function scopeOld($query, int $days = 30)
    {
        return $query->where('created_at', '<', now()->subDays($days));
    }

    /**
     * Check if cart item is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if cart item is submitted
     */
    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * Check if cart item is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired';
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
     * Get number from bet data
     */
    public function getNumberAttribute(): ?string
    {
        return $this->bet_data['number'] ?? null;
    }

    /**
     * Get channels from bet data
     */
    public function getChannelsAttribute(): ?array
    {
        return $this->bet_data['channels'] ?? null;
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->total_amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Get cart item display name
     */
    public function getDisplayNameAttribute(): string
    {
        $betType = $this->bet_type ?? 'Unknown';
        $number = $this->number ?? 'N/A';
        return "Cart Item - {$betType} #{$number}";
    }

    /**
     * Get expanded numbers count
     */
    public function getExpandedNumbersCountAttribute(): int
    {
        return count($this->expanded_numbers ?? []);
    }

    /**
     * Get total channel weight
     */
    public function getTotalChannelWeightAttribute(): float
    {
        return array_sum($this->channel_weights ?? []);
    }

    /**
     * Check if cart item has expanded numbers
     */
    public function hasExpandedNumbers(): bool
    {
        return !empty($this->expanded_numbers);
    }

    /**
     * Check if cart item has channel weights
     */
    public function hasChannelWeights(): bool
    {
        return !empty($this->channel_weights);
    }
}

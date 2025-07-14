<?php

declare(strict_types=1);

namespace App\Infrastructure\AgentSettings\Models;

use App\Infrastructure\Agent\Models\EloquentAgent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EloquentAgentSettings extends Model
{
    use HasFactory;

    protected $table = 'agent_settings';

    protected $fillable = [
        'agent_id',
        'payout_profile',
        'payout_profile_source_agent_id',
        'has_custom_payout_profile',
        'commission_rate',
        'sharing_rate',
        'max_commission_sharing_rate',
        'effective_payout_profile',
        'effective_payout_source_agent_id',
        'effective_commission_rate',
        'effective_sharing_rate',
        'is_computed',
        'computed_at',
        'cache_expires_at',
        'betting_limits',
        'blocked_numbers',
        'auto_settlement',
        'is_active',
    ];

    protected $casts = [
        'payout_profile' => 'array',
        'has_custom_payout_profile' => 'boolean',
        'commission_rate' => 'decimal:2',
        'sharing_rate' => 'decimal:2',
        'max_commission_sharing_rate' => 'decimal:2',
        'effective_payout_profile' => 'array',
        'effective_commission_rate' => 'decimal:2',
        'effective_sharing_rate' => 'decimal:2',
        'is_computed' => 'boolean',
        'computed_at' => 'datetime',
        'cache_expires_at' => 'datetime',
        'betting_limits' => 'array',
        'blocked_numbers' => 'array',
        'auto_settlement' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the agent that owns these settings
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(EloquentAgent::class, 'agent_id');
    }

    /**
     * Get the agent that is the source of the payout profile
     */
    public function payoutProfileSource(): BelongsTo
    {
        return $this->belongsTo(EloquentAgent::class, 'payout_profile_source_agent_id');
    }

    /**
     * Get the agent that is the source of the effective payout profile
     */
    public function effectivePayoutSource(): BelongsTo
    {
        return $this->belongsTo(EloquentAgent::class, 'effective_payout_source_agent_id');
    }

    /**
     * Scope for active settings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for settings with custom payout profile
     */
    public function scopeWithCustomPayoutProfile($query)
    {
        return $query->where('has_custom_payout_profile', true);
    }

    /**
     * Scope for settings with inherited payout profile
     */
    public function scopeWithInheritedPayoutProfile($query)
    {
        return $query->where('has_custom_payout_profile', false);
    }

    /**
     * Scope for computed settings
     */
    public function scopeComputed($query)
    {
        return $query->where('is_computed', true);
    }

    /**
     * Scope for non-computed settings
     */
    public function scopeNotComputed($query)
    {
        return $query->where('is_computed', false);
    }

    /**
     * Scope for settings with expired cache
     */
    public function scopeWithExpiredCache($query)
    {
        return $query->where('cache_expires_at', '<', now());
    }

    /**
     * Scope for settings with commission rate above threshold
     */
    public function scopeWithCommissionRateAbove($query, float $threshold)
    {
        return $query->where('commission_rate', '>', $threshold);
    }

    /**
     * Scope for settings with sharing rate above threshold
     */
    public function scopeWithSharingRateAbove($query, float $threshold)
    {
        return $query->where('sharing_rate', '>', $threshold);
    }

    /**
     * Scope for settings with auto settlement enabled
     */
    public function scopeWithAutoSettlement($query)
    {
        return $query->where('auto_settlement', true);
    }

    /**
     * Check if payout profile is custom
     */
    public function hasCustomPayoutProfile(): bool
    {
        return $this->has_custom_payout_profile;
    }

    /**
     * Check if settings are computed
     */
    public function isComputed(): bool
    {
        return $this->is_computed;
    }

    /**
     * Check if cache is expired
     */
    public function isCacheExpired(): bool
    {
        return $this->cache_expires_at && $this->cache_expires_at->isPast();
    }

    /**
     * Check if auto settlement is enabled
     */
    public function hasAutoSettlement(): bool
    {
        return $this->auto_settlement;
    }

    /**
     * Get effective payout profile with fallback
     */
    public function getEffectivePayoutProfileAttribute($value)
    {
        if ($value) {
            return json_decode((string) $value, true);
        }

        // Fallback to custom payout profile if no effective profile
        return $this->payout_profile;
    }

    /**
     * Get commission rate with fallback
     */
    public function getEffectiveCommissionRateAttribute($value)
    {
        return $value ?? $this->commission_rate;
    }

    /**
     * Get sharing rate with fallback
     */
    public function getEffectiveSharingRateAttribute($value)
    {
        return $value ?? $this->sharing_rate;
    }

    /**
     * Get total commission and sharing rate
     */
    public function getTotalRateAttribute(): float
    {
        return ($this->effective_commission_rate ?? 0) + ($this->effective_sharing_rate ?? 0);
    }

    /**
     * Get available commission sharing capacity
     */
    public function getAvailableCommissionCapacityAttribute(): float
    {
        return $this->max_commission_sharing_rate - $this->total_rate;
    }

    /**
     * Check if number is blocked
     */
    public function isNumberBlocked(string $number): bool
    {
        return in_array($number, $this->blocked_numbers ?? []);
    }

    /**
     * Get betting limit for specific type
     */
    public function getBettingLimit(string $type): ?array
    {
        return $this->betting_limits[$type] ?? null;
    }

    /**
     * Check if agent can bet on specific number
     */
    public function canBetOnNumber(string $number): bool
    {
        return ! $this->isNumberBlocked($number);
    }

    /**
     * Check if bet amount is within limits
     */
    public function isBetAmountValid(string $type, float $amount): bool
    {
        $limits = $this->getBettingLimit($type);

        if ($limits === null || $limits === []) {
            return true; // No limits set
        }

        $min = $limits['min'] ?? 0;
        $max = $limits['max'] ?? PHP_FLOAT_MAX;

        return $amount >= $min && $amount <= $max;
    }
}

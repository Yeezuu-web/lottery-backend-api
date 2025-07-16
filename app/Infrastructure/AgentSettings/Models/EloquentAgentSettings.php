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
        'daily_limit',
        'max_commission',
        'max_share',
        'number_limits',
        'blocked_numbers',
        'is_active',
    ];

    protected $casts = [
        'daily_limit' => 'decimal:2',
        'max_commission' => 'decimal:2',
        'max_share' => 'decimal:2',
        'number_limits' => 'array',
        'blocked_numbers' => 'array',
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
     * Scope for active settings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for inactive settings
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Check if agent has daily limit
     */
    public function hasDailyLimit(): bool
    {
        return $this->daily_limit !== null;
    }

    /**
     * Check if number is blocked
     */
    public function isNumberBlocked(string $number): bool
    {
        return in_array($number, $this->blocked_numbers ?? []);
    }

    /**
     * Check if agent has number limit for specific game type and number
     */
    public function hasNumberLimit(string $gameType, string $number): bool
    {
        return isset($this->number_limits[$gameType][$number]);
    }

    /**
     * Get number limit for specific game type and number
     */
    public function getNumberLimit(string $gameType, string $number): ?float
    {
        return $this->number_limits[$gameType][$number] ?? null;
    }

    /**
     * Get all number limits for specific game type
     */
    public function getNumberLimitsForGameType(string $gameType): array
    {
        return $this->number_limits[$gameType] ?? [];
    }

    /**
     * Check if agent can bet on specific number
     */
    public function canBetOnNumber(string $number): bool
    {
        return ! $this->isNumberBlocked($number);
    }
}

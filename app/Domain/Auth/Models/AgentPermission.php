<?php

declare(strict_types=1);

namespace App\Domain\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AgentPermission extends Model
{
    protected $fillable = [
        'agent_id',
        'permission_id',
        'granted_by',
        'granted_at',
        'expires_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the agent that has this permission
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Agent\Models\Agent::class);
    }

    /**
     * Get the permission
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    /**
     * Get the agent who granted this permission
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Agent\Models\Agent::class, 'granted_by');
    }

    /**
     * Check if this permission is currently active
     */
    public function isActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return ! ($this->expires_at && $this->expires_at < now());
    }

    /**
     * Check if this permission has expired
     */
    public function hasExpired(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    /**
     * Scope to get active permissions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to get expired permissions
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope to get permissions granted by a specific agent
     */
    public function scopeGrantedBy($query, int $agentId)
    {
        return $query->where('granted_by', $agentId);
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Permission extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'category',
        'agent_types',
        'is_active',
    ];

    protected $casts = [
        'agent_types' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get all agents that have this permission
     */
    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Domain\Agent\Models\Agent::class,
            'agent_permissions',
            'permission_id',
            'agent_id'
        )
            ->withPivot([
                'granted_by',
                'granted_at',
                'expires_at',
                'is_active',
                'metadata',
            ])
            ->withTimestamps();
    }

    /**
     * Get all agent permission records for this permission
     */
    public function agentPermissions(): HasMany
    {
        return $this->hasMany(AgentPermission::class);
    }

    /**
     * Get active agent permissions only
     */
    public function activeAgentPermissions(): HasMany
    {
        return $this->agentPermissions()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Check if this permission can be assigned to a specific agent type
     */
    public function canBeAssignedTo(string $agentType): bool
    {
        return $this->agent_types === null || in_array($agentType, $this->agent_types);
    }

    /**
     * Scope to get permissions by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get active permissions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get permissions available for specific agent type
     */
    public function scopeForAgentType($query, string $agentType)
    {
        return $query->where(function ($q) use ($agentType): void {
            $q->whereNull('agent_types')
                ->orWhereJsonContains('agent_types', $agentType);
        });
    }
}

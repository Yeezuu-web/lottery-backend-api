<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Agent\Models\Agent;
use App\Domain\Auth\Models\AgentPermission;
use App\Domain\Auth\Models\Permission;
use App\Infrastructure\Agent\Models\EloquentAgent;
use App\Shared\Exceptions\ValidationException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class DatabaseAuthorizationService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Check if agent has a specific permission
     */
    public function hasPermission(int $agentId, string $permission): bool
    {
        $cacheKey = sprintf('agent_permission_%d_%s', $agentId, $permission);

        return Cache::remember($cacheKey, self::CACHE_TTL, fn (): bool => $this->checkPermissionFromDatabase($agentId, $permission));
    }

    /**
     * Check if agent has any of the given permissions
     */
    public function hasAnyPermission(int $agentId, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($agentId, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if agent has all of the given permissions
     */
    public function hasAllPermissions(int $agentId, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($agentId, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all permissions for an agent
     */
    public function getAgentPermissions(int $agentId): array
    {
        $cacheKey = 'agent_permissions_'.$agentId;

        return Cache::remember($cacheKey, self::CACHE_TTL, fn (): array => $this->getPermissionsFromDatabase($agentId));
    }

    /**
     * Grant permission to an agent
     */
    public function grantPermission(
        int $agentId,
        string $permissionName,
        int $grantedBy,
        ?Carbon $expiresAt = null,
        ?array $metadata = null
    ): AgentPermission {
        $agent = EloquentAgent::findOrFail($agentId);
        $permission = Permission::where('name', $permissionName)->firstOrFail();

        // Check if permission can be assigned to this agent type
        if (! $permission->canBeAssignedTo($agent->agent_type)) {
            throw new ValidationException(
                sprintf("Permission '%s' cannot be assigned to agent type '%s'", $permissionName, $agent->agent_type)
            );
        }

        // Check if granting agent has permission to grant this permission
        if (! $this->canGrantPermission($grantedBy, $permissionName)) {
            throw new ValidationException(
                sprintf("You don't have permission to grant '%s'", $permissionName)
            );
        }

        // Check if permission already exists
        $existingPermission = AgentPermission::where('agent_id', $agentId)
            ->where('permission_id', $permission->id)
            ->first();

        if ($existingPermission) {
            // Update existing permission
            $existingPermission->update([
                'granted_by' => $grantedBy,
                'granted_at' => now(),
                'expires_at' => $expiresAt,
                'is_active' => true,
                'metadata' => $metadata,
            ]);

            $agentPermission = $existingPermission;
        } else {
            // Create new permission
            $agentPermission = AgentPermission::create([
                'agent_id' => $agentId,
                'permission_id' => $permission->id,
                'granted_by' => $grantedBy,
                'granted_at' => now(),
                'expires_at' => $expiresAt,
                'is_active' => true,
                'metadata' => $metadata,
            ]);
        }

        // Clear cache
        $this->clearAgentPermissionCache($agentId);

        return $agentPermission;
    }

    /**
     * Revoke permission from an agent
     */
    public function revokePermission(int $agentId, string $permissionName, int $revokedBy): bool
    {
        $permission = Permission::where('name', $permissionName)->first();

        if (! $permission) {
            return false;
        }

        // Check if revoking agent has permission to revoke this permission
        if (! $this->canRevokePermission($revokedBy, $permissionName)) {
            throw new ValidationException(
                sprintf("You don't have permission to revoke '%s'", $permissionName)
            );
        }

        $result = AgentPermission::where('agent_id', $agentId)
            ->where('permission_id', $permission->id)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        // Clear cache
        $this->clearAgentPermissionCache($agentId);

        return $result > 0;
    }

    /**
     * Check if agent can manage another agent
     */
    public function canManageAgent(int $managerId, int $targetAgentId): bool
    {
        // Company can manage anyone
        if ($this->hasPermission($managerId, 'manage_all_agents')) {
            return true;
        }

        // Check if manager can manage sub-agents and target is a downline
        if ($this->hasPermission($managerId, 'manage_sub_agents')) {
            return $this->isDownlineOf($targetAgentId, $managerId);
        }

        return false;
    }

    /**
     * Check if agent can manage wallets
     */
    public function canManageWallets(int $agentId): bool
    {
        return $this->hasAnyPermission($agentId, [
            'manage_all_wallets',
            'manage_financial_settings',
        ]);
    }

    /**
     * Check if agent can view reports
     */
    public function canViewReports(int $agentId, ?int $targetAgentId = null): bool
    {
        // Can view all reports
        if ($this->hasPermission($agentId, 'view_all_reports')) {
            return true;
        }

        // Can view sub-agent reports
        if ($this->hasPermission($agentId, 'view_sub_reports')) {
            return $targetAgentId === null || $this->isDownlineOf($targetAgentId, $agentId);
        }

        // Can view own reports
        if ($this->hasPermission($agentId, 'view_own_reports')) {
            return $targetAgentId === null || $targetAgentId === $agentId;
        }

        return false;
    }

    /**
     * Get available permissions for an agent type
     */
    public function getAvailablePermissions(string $agentType): Collection
    {
        return Permission::active()
            ->forAgentType($agentType)
            ->orderBy('category')
            ->orderBy('name')
            ->get();
    }

    /**
     * Sync permissions for an agent based on their type (fallback system)
     */
    public function syncDefaultPermissions(int $agentId): void
    {
        $agent = EloquentAgent::findOrFail($agentId);
        $defaultPermissions = $this->getDefaultPermissionsForType($agent->agent_type);

        foreach ($defaultPermissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();

            if ($permission) {
                $exists = AgentPermission::where('agent_id', $agentId)
                    ->where('permission_id', $permission->id)
                    ->exists();

                if (! $exists) {
                    AgentPermission::create([
                        'agent_id' => $agentId,
                        'permission_id' => $permission->id,
                        'granted_by' => null, // System granted
                        'granted_at' => now(),
                        'is_active' => true,
                    ]);
                }
            }
        }

        // Clear cache
        $this->clearAgentPermissionCache($agentId);
    }

    /**
     * Get permission inheritance chain
     */
    public function getPermissionInheritanceChain(int $agentId): array
    {
        $agent = EloquentAgent::findOrFail($agentId);
        $chain = [];

        // Get direct permissions
        $directPermissions = $this->getPermissionsFromDatabase($agentId);
        $chain['direct'] = $directPermissions;

        // Get inherited permissions from upline (if applicable)
        if ($agent->upline_id) {
            $inheritedPermissions = $this->getInheritedPermissions($agentId);
            $chain['inherited'] = $inheritedPermissions;
        }

        return $chain;
    }

    /**
     * Bulk update permissions for multiple agents
     */
    public function bulkUpdatePermissions(array $agentIds, array $permissions, int $grantedBy): void
    {
        DB::transaction(function () use ($agentIds, $permissions, $grantedBy): void {
            foreach ($agentIds as $agentId) {
                foreach ($permissions as $permissionName) {
                    $this->grantPermission($agentId, $permissionName, $grantedBy);
                }
            }
        });
    }

    /**
     * Check permission from database
     */
    private function checkPermissionFromDatabase(int $agentId, string $permissionName): bool
    {
        return AgentPermission::whereHas('permission', function ($query) use ($permissionName): void {
            $query->where('name', $permissionName)
                ->where('is_active', true);
        })
            ->where('agent_id', $agentId)
            ->active()
            ->exists();
    }

    /**
     * Get permissions from database
     */
    private function getPermissionsFromDatabase(int $agentId): array
    {
        return AgentPermission::with('permission')
            ->where('agent_id', $agentId)
            ->active()
            ->get()
            ->map(fn ($agentPermission) => $agentPermission->permission->name)
            ->toArray();
    }

    /**
     * Check if agent can grant a specific permission
     */
    private function canGrantPermission(int $agentId, string $permissionName): bool
    {
        // Company can grant any permission
        if ($this->hasPermission($agentId, 'manage_all_agents')) {
            return true;
        }

        // Check specific grant permissions
        if ($this->hasPermission($agentId, 'grant_'.$permissionName)) {
            return true;
        }

        return $this->hasPermission($agentId, 'grant_permissions');
    }

    /**
     * Check if agent can revoke a specific permission
     */
    private function canRevokePermission(int $agentId, string $permissionName): bool
    {
        // Company can revoke any permission
        if ($this->hasPermission($agentId, 'manage_all_agents')) {
            return true;
        }

        // Check specific revoke permissions
        if ($this->hasPermission($agentId, 'revoke_'.$permissionName)) {
            return true;
        }

        return $this->hasPermission($agentId, 'revoke_permissions');
    }

    /**
     * Check if target agent is downline of manager
     */
    private function isDownlineOf(int $targetAgentId, int $managerId): bool
    {
        $targetAgent = EloquentAgent::findOrFail($targetAgentId);

        // Walk up the hierarchy
        $currentAgent = $targetAgent;
        while ($currentAgent->upline_id) {
            if ($currentAgent->upline_id === $managerId) {
                return true;
            }

            $currentAgent = $currentAgent->parent;
        }

        return false;
    }

    /**
     * Get default permissions for agent type
     */
    private function getDefaultPermissionsForType(string $agentType): array
    {
        return match ($agentType) {
            'company' => [
                'manage_all_agents',
                'view_all_reports',
                'manage_system_settings',
                'manage_financial_settings',
                'manage_all_wallets',
                'grant_permissions',
                'revoke_permissions',
            ],
            'super senior' => [
                'manage_sub_agents',
                'view_sub_reports',
                'manage_financial_settings',
                'manage_sub_wallets',
            ],
            'senior' => [
                'manage_sub_agents',
                'view_sub_reports',
                'manage_sub_wallets',
            ],
            'master' => [
                'manage_sub_agents',
                'view_sub_reports',
                'manage_sub_wallets',
            ],
            'agent' => [
                'view_own_reports',
                'manage_own_wallet',
            ],
            'member' => [
                'place_bets',
                'view_own_bets',
                'manage_profile',
            ],
            default => []
        };
    }

    /**
     * Get inherited permissions from upline
     */
    private function getInheritedPermissions(int $agentId): array
    {
        $agent = EloquentAgent::findOrFail($agentId);

        if (! $agent->upline_id) {
            return [];
        }

        // Get permissions that can be inherited
        $inheritablePermissions = [
            'view_sub_reports',
            'manage_sub_agents',
            // Add more inheritable permissions as needed
        ];

        $uplinePermissions = $this->getPermissionsFromDatabase($agent->upline_id);

        return array_intersect($uplinePermissions, $inheritablePermissions);
    }

    /**
     * Clear agent permission cache
     */
    private function clearAgentPermissionCache(int $agentId): void
    {
        Cache::forget('agent_permissions_'.$agentId);

        // Clear individual permission caches
        $permissions = Permission::pluck('name');
        foreach ($permissions as $permission) {
            Cache::forget(sprintf('agent_permission_%d_%s', $agentId, $permission));
        }
    }
}

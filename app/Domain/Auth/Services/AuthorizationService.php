<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Agent\Models\Agent;
use App\Domain\Agent\ValueObjects\AgentType;
use App\Domain\Auth\ValueObjects\JWTToken;
use App\Shared\Exceptions\ValidationException;

final class AuthorizationService
{
    /**
     * Check if agent has specific permission
     */
    public function hasPermission(Agent $agent, string $permission, string $audience): bool
    {
        $permissions = $this->getPermissions($agent, $audience);

        return in_array($permission, $permissions, true);
    }

    /**
     * Check if agent can perform action on target agent
     */
    public function canManageAgent(Agent $actor, Agent $target, string $action): bool
    {
        return match ($action) {
            'view' => $this->canViewAgent($actor, $target),
            'edit' => $this->canEditAgent($actor, $target),
            'delete' => $this->canDeleteAgent($actor, $target),
            'create_sub_agent' => $this->canCreateSubAgent($actor, $target),
            'transfer_funds' => $this->canTransferFunds($actor, $target),
            'view_reports' => $this->canViewReports($actor, $target),
            default => false
        };
    }

    /**
     * Check if agent can access specific resource
     */
    public function canAccessResource(Agent $agent, string $resource, string $action, string $audience): bool
    {
        $permission = $this->getResourcePermission($resource, $action);

        return $this->hasPermission($agent, $permission, $audience);
    }

    /**
     * Get all permissions for agent in specific audience
     */
    public function getPermissions(Agent $agent, string $audience): array
    {
        $basePermissions = $this->getBasePermissions($audience);
        $typePermissions = $this->getTypeSpecificPermissions($agent->agentType(), $audience);
        $hierarchyPermissions = $this->getHierarchyPermissions($agent);

        return array_unique(array_merge($basePermissions, $typePermissions, $hierarchyPermissions));
    }

    /**
     * Get permissions from JWT token
     */
    public function getTokenPermissions(JWTToken $token): array
    {
        return $token->getPermissions();
    }

    /**
     * Validate if agent has required permissions for action
     */
    public function authorize(Agent $agent, array $requiredPermissions, string $audience): void
    {
        $agentPermissions = $this->getPermissions($agent, $audience);

        foreach ($requiredPermissions as $permission) {
            if (! in_array($permission, $agentPermissions, true)) {
                throw new ValidationException(sprintf("Insufficient permissions: missing '%s'", $permission));
            }
        }
    }

    /**
     * Check if agent can view another agent
     */
    private function canViewAgent(Agent $actor, Agent $target): bool
    {
        // Can view self
        if ($actor->id() === $target->id()) {
            return true;
        }

        // Can view direct downlines
        if ($actor->canManage($target)) {
            return true;
        }

        // Company can view all
        return $actor->isCompany();
    }

    /**
     * Check if agent can edit another agent
     */
    private function canEditAgent(Agent $actor, Agent $target): bool
    {
        // Cannot edit self through this method
        if ($actor->id() === $target->id()) {
            return false;
        }

        // Can edit direct downlines only
        return $actor->canManage($target);
    }

    /**
     * Check if agent can delete another agent
     */
    private function canDeleteAgent(Agent $actor, Agent $target): bool
    {
        // Cannot delete self
        if ($actor->id() === $target->id()) {
            return false;
        }

        // Can delete direct downlines only
        return $actor->canManage($target);
    }

    /**
     * Check if agent can create sub-agents under target
     */
    private function canCreateSubAgent(Agent $actor, Agent $target): bool
    {
        // Must be able to manage the target
        if (! $actor->canManage($target)) {
            return false;
        }

        // Target must be able to have sub-agents
        return $target->agentType()->canManageSubAgents();
    }

    /**
     * Check if agent can transfer funds involving target
     */
    private function canTransferFunds(Agent $actor, Agent $target): bool
    {
        // Can transfer own funds
        if ($actor->id() === $target->id()) {
            return true;
        }

        // Can manage target's funds if can manage target
        return $actor->canManage($target);
    }

    /**
     * Check if agent can view reports for target
     */
    private function canViewReports(Agent $actor, Agent $target): bool
    {
        // Can view own reports
        if ($actor->id() === $target->id()) {
            return true;
        }

        // Can view downline reports
        return $actor->canManage($target);
    }

    /**
     * Get base permissions for audience
     */
    private function getBasePermissions(string $audience): array
    {
        return match ($audience) {
            'upline' => [
                'view_dashboard',
                'view_profile',
                'edit_profile',
                'view_own_reports',
                'view_own_transactions',
            ],
            'member' => [
                'place_bets',
                'view_orders',
                'view_balance',
                'view_profile',
                'edit_profile',
                'view_own_transactions',
            ],
            default => []
        };
    }

    /**
     * Get type-specific permissions
     */
    private function getTypeSpecificPermissions(AgentType $agentType, string $audience): array
    {
        if ($audience === 'member') {
            return []; // Members have same permissions regardless of type
        }

        return match ($agentType->value()) {
            AgentType::COMPANY => [
                'manage_all_agents',
                'view_all_reports',
                'view_all_transactions',
                'manage_system_settings',
                'manage_financial_settings',
                'view_system_logs',
                'manage_payout_profiles',
                'view_all_transactions',
                'manage_sub_agents',
            ],
            AgentType::SUPER_SENIOR => [
                'view_all_reports',
                'manage_sub_agents',
                'view_sub_reports',
                'manage_financial_settings',
                'view_sub_transactions',
                'manage_commissions',
            ],
            AgentType::SENIOR => [
                'manage_sub_agents',
                'view_sub_reports',
                'view_sub_transactions',
                'manage_commissions',
            ],
            AgentType::MASTER => [
                'manage_sub_agents',
                'view_sub_reports',
                'view_sub_transactions',
            ],
            AgentType::AGENT => [
                'view_own_reports',
                'view_own_transactions',
            ],
            default => []
        };
    }

    /**
     * Get hierarchy-based permissions
     */
    private function getHierarchyPermissions(Agent $agent): array
    {
        $permissions = [];

        // Add permissions based on hierarchy level
        $hierarchyLevel = $agent->getHierarchyLevel();

        if ($hierarchyLevel <= 2) { // Company and Super Senior
            $permissions[] = 'manage_multiple_currencies';
            $permissions[] = 'view_financial_reports';
        }

        if ($hierarchyLevel <= 3) { // Senior and above
            $permissions[] = 'manage_betting_limits';
            $permissions[] = 'view_performance_metrics';
        }

        return $permissions;
    }

    /**
     * Get resource permission mapping
     */
    private function getResourcePermission(string $resource, string $action): string
    {
        return match ($resource) {
            'agents' => match ($action) {
                'index' => 'view_sub_agents',
                'show' => 'view_agent',
                'create' => 'create_sub_agent',
                'update' => 'edit_agent',
                'destroy' => 'delete_agent',
                default => 'access_agents'
            },
            'reports' => match ($action) {
                'index' => 'view_reports',
                'show' => 'view_report',
                'export' => 'export_reports',
                default => 'access_reports'
            },
            'wallets' => match ($action) {
                'index' => 'view_wallets',
                'show' => 'view_wallet',
                'transfer' => 'transfer_funds',
                'credit' => 'credit_wallet',
                'debit' => 'debit_wallet',
                default => 'access_wallets'
            },
            'settings' => match ($action) {
                'index' => 'view_settings',
                'update' => 'manage_settings',
                default => 'access_settings'
            },
            default => sprintf('%s_%s', $action, $resource)
        };
    }
}

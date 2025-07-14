<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Agent\Models\Agent;
use App\Domain\Agent\ValueObjects\AgentType;
use App\Domain\Auth\Contracts\AuthenticationDomainServiceInterface;
use App\Domain\Auth\Exceptions\AuthenticationException;
use App\Shared\Exceptions\ValidationException;

final class AuthenticationDomainService implements AuthenticationDomainServiceInterface
{
    /**
     * Validate if agent can authenticate for specific audience
     */
    public function validateAuthentication(Agent $agent, string $audience): void
    {
        // Check if agent is active
        if (! $agent->isActive()) {
            throw AuthenticationException::agentNotActive();
        }

        // Check audience compatibility
        if (! $this->canAuthenticateForAudience($agent, $audience)) {
            throw AuthenticationException::invalidAudience(
                $agent->agentType()->value(),
                $audience
            );
        }
    }

    /**
     * Check if agent can authenticate for specific audience
     */
    public function canAuthenticateForAudience(Agent $agent, string $audience): bool
    {
        $agentType = $agent->agentType();

        return match ($audience) {
            'upline' => $this->canAccessUpline($agentType),
            'member' => $this->canAccessMember($agentType),
            default => throw new ValidationException('Invalid audience: '.$audience)
        };
    }

    /**
     * Get permissions for agent based on type and audience
     */
    public function getPermissions(Agent $agent, string $audience): array
    {
        $basePermissions = $this->getBasePermissions($audience);
        $typePermissions = $this->getTypeSpecificPermissions($agent->agentType(), $audience);

        return array_merge($basePermissions, $typePermissions);
    }

    /**
     * Validate audience parameter
     */
    public function validateAudience(string $audience): void
    {
        $validAudiences = ['upline', 'member'];
        if (! in_array($audience, $validAudiences, true)) {
            throw new ValidationException('Invalid audience. Must be: '.implode(', ', $validAudiences));
        }
    }

    /**
     * Check if agent type can access upline dashboard
     */
    private function canAccessUpline(AgentType $agentType): bool
    {
        // All agent types except member can access upline
        return ! $agentType->isMember();
    }

    /**
     * Check if agent type can access member betting interface
     */
    private function canAccessMember(AgentType $agentType): bool
    {
        // Only members can access member interface
        return $agentType->isMember();
    }

    /**
     * Get base permissions for audience
     */
    private function getBasePermissions(string $audience): array
    {
        return match ($audience) {
            'upline' => [
                'view_dashboard',
                'view_reports',
                'manage_profile',
            ],
            'member' => [
                'place_bets',
                'view_orders',
                'view_balance',
                'manage_profile',
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
            // Members have same permissions regardless of sub-type
            return [];
        }

        // Upline permissions based on agent type
        return match ($agentType->value()) {
            AgentType::COMPANY => [
                'manage_all_agents',
                'view_all_reports',
                'manage_system_settings',
                'manage_financial_settings',
            ],
            AgentType::SUPER_SENIOR => [
                'manage_sub_agents',
                'view_sub_reports',
                'manage_financial_settings',
            ],
            AgentType::SENIOR => [
                'manage_sub_agents',
                'view_sub_reports',
            ],
            AgentType::MASTER => [
                'manage_sub_agents',
                'view_sub_reports',
            ],
            AgentType::AGENT => [
                'view_own_reports',
            ],
            default => []
        };
    }
}

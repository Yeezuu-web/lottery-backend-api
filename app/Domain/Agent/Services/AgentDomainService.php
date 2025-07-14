<?php

declare(strict_types=1);

namespace App\Domain\Agent\Services;

use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Agent\Exceptions\AgentException;
use App\Domain\Agent\Models\Agent;
use App\Domain\Agent\ValueObjects\AgentType;
use App\Domain\Agent\ValueObjects\Username;
use App\Shared\Exceptions\ValidationException;

final readonly class AgentDomainService
{
    public function __construct(private AgentRepositoryInterface $agentRepository) {}

    /**
     * Validate if agent can create another agent
     */
    public function validateAgentCreation(Agent $creator, AgentType $targetType, string $targetUsername): void
    {
        // Check if creator can create this type of agent
        if (! $creator->canCreateAgentType($targetType)) {
            throw AgentException::cannotCreateAgentType($creator->username()->value(), $targetType->value());
        }

        // Validate username format
        $username = new Username($targetUsername);
        if (! $username->isValidForAgentType($targetType)) {
            throw new ValidationException(
                sprintf("Username '%s' is not valid for agent type '%s'", $targetUsername, $targetType->value())
            );
        }

        // Check if username already exists
        if ($this->agentRepository->usernameExists($username)) {
            throw new ValidationException(sprintf("Username '%s' already exists", $targetUsername));
        }

        // Validate hierarchy relationship
        if (! $username->isChildOf($creator->username())) {
            throw AgentException::invalidHierarchy($targetUsername, $creator->username()->value());
        }
    }

    /**
     * Get agents that can be viewed by given agent (direct downlines)
     */
    public function getViewableAgents(Agent $agent): array
    {
        return $this->agentRepository->getDirectDownlines($agent->id());
    }

    /**
     * Get all agents in hierarchy tree
     */
    public function getHierarchyTree(Agent $agent): array
    {
        $downlines = $this->agentRepository->getHierarchyDownlines($agent->id());

        return $this->buildHierarchyTree($downlines, $agent->id());
    }

    /**
     * Validate agent management permissions
     */
    public function validateManagementPermission(Agent $manager, Agent $target): void
    {
        if (! $manager->canManage($target)) {
            throw AgentException::cannotManageAgent($manager->username()->value(), $target->username()->value());
        }
    }

    /**
     * Get next available username for creating sub-agent
     */
    public function generateNextUsername(Agent $parentAgent, AgentType $targetType): Username
    {
        return $this->agentRepository->getNextAvailableUsername($targetType, $parentAgent->username());
    }

    /**
     * Validate agent hierarchy consistency
     */
    public function validateHierarchyConsistency(Agent $agent): void
    {
        // If agent has upline, verify the upline exists and is valid
        if ($agent->uplineId() !== null) {
            $upline = $this->agentRepository->findById($agent->uplineId());

            if (! $upline instanceof Agent) {
                throw new AgentException(sprintf("Upline agent with ID '%d' not found", $agent->uplineId()));
            }

            // Check if this agent is a valid child of the upline
            if (! $agent->isDirectChildOf($upline)) {
                throw new AgentException(
                    sprintf("Agent '%s' is not a valid child of '%s'", $agent->username()->value(), $upline->username()->value())
                );
            }
        }
    }

    /**
     * Get agent statistics
     */
    public function getAgentStatistics(Agent $agent): array
    {
        $directDownlines = $this->agentRepository->getDirectDownlines($agent->id());
        $allDownlines = $this->agentRepository->getHierarchyDownlines($agent->id());

        $stats = [
            'direct_downlines' => count($directDownlines),
            'total_downlines' => count($allDownlines),
            'hierarchy_level' => $agent->getHierarchyLevel(),
            'agent_type' => $agent->agentType()->value(),
            'agent_type_display' => $agent->agentType()->getDisplayName(),
            'downlines_by_type' => [],
        ];

        // Count downlines by type
        foreach ($allDownlines as $downline) {
            $type = $downline->agentType()->value();
            $stats['downlines_by_type'][$type] = ($stats['downlines_by_type'][$type] ?? 0) + 1;
        }

        return $stats;
    }

    /**
     * Find agents by username pattern (for hierarchy navigation)
     */
    public function findAgentsByUsernamePattern(string $pattern): array
    {
        return $this->agentRepository->search(['username_pattern' => $pattern]);
    }

    /**
     * Validate agent business rules
     */
    public function validateBusinessRules(Agent $agent): void
    {
        // Validate hierarchy consistency
        $this->validateHierarchyConsistency($agent);

        // Validate agent type specific rules
        $this->validateAgentTypeRules($agent);

        // Validate username rules
        $this->validateUsernameRules($agent);
    }

    /**
     * Check if agent can drill down to specific agent
     */
    public function canDrillDownTo(Agent $viewer, Agent $target): bool
    {
        // Must be able to manage the target
        if (! $viewer->canManage($target)) {
            return false;
        }

        // Must be in the same hierarchy path
        return $viewer->isInHierarchyPath($target);
    }

    /**
     * Build hierarchy tree structure
     */
    private function buildHierarchyTree(array $agents, int $parentId): array
    {
        $tree = [];

        foreach ($agents as $agent) {
            if ($agent->uplineId() === $parentId) {
                $children = $this->buildHierarchyTree($agents, $agent->id());
                $agentData = $agent->getDisplayInfo();
                $agentData['children'] = $children;
                $tree[] = $agentData;
            }
        }

        return $tree;
    }

    /**
     * Validate agent type specific rules
     */
    private function validateAgentTypeRules(Agent $agent): void
    {
        // Company agents should not have upline
        if ($agent->isCompany() && $agent->uplineId() !== null) {
            throw new AgentException('Company agents cannot have upline');
        }

        // Non-company agents should have upline
        if (! $agent->isCompany() && $agent->uplineId() === null) {
            throw new AgentException('Non-company agents must have upline');
        }
    }

    /**
     * Validate username rules
     */
    private function validateUsernameRules(Agent $agent): void
    {
        // Username should match agent type
        if (! $agent->username()->isValidForAgentType($agent->agentType())) {
            throw AgentException::invalidUsernameForAgentType($agent->username()->value(), $agent->agentType()->value());
            // "Username '{$agent->username()->value()}' is not valid for agent type '{$agent->agentType()->value()}'"
        }

        // If agent has upline, username should be child of upline username
        if ($agent->uplineId() !== null) {
            $upline = $this->agentRepository->findById($agent->uplineId());
            if ($upline && ! $agent->username()->isChildOf($upline->username())) {
                throw AgentException::invalidHierarchy($agent->username()->value(), $upline->username()->value());
            }
        }
    }
}

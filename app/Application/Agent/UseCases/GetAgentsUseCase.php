<?php

namespace App\Application\Agent\UseCases;

use App\Application\Agent\Commands\GetAgentsCommand;
use App\Application\Agent\Responses\AgentListResponse;
use App\Application\Agent\Responses\AgentResponse;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Agent\Exceptions\AgentException;
use App\Domain\Agent\Models\Agent;
use App\Domain\Agent\Services\AgentDomainService;
use App\Domain\Agent\ValueObjects\AgentType;

final class GetAgentsUseCase
{
    public function __construct(
        private readonly AgentRepositoryInterface $agentRepository,
        private readonly AgentDomainService $agentDomainService
    ) {}

    public function execute(GetAgentsCommand $command): AgentListResponse
    {
        // Find the viewer agent
        $viewer = $this->agentRepository->findById($command->getViewerId());
        if (! $viewer) {
            throw AgentException::notFound($command->getViewerId());
        }

        // Check if viewer is active
        if (! $viewer->isActive()) {
            throw AgentException::agentInactive($viewer->username()->value());
        }

        // Determine which agents to get
        $agents = $this->getAgentsForViewer($viewer, $command);

        // Convert to response DTOs
        $agentResponses = [];
        foreach ($agents as $agent) {
            $statistics = $this->agentDomainService->getAgentStatistics($agent);
            $permissions = $this->getAgentPermissions($viewer, $agent);
            $agentResponses[] = AgentResponse::fromDomain($agent, $permissions, $statistics);
        }

        // Get total count for pagination
        $total = $this->getTotalCount($viewer, $command);

        // Prepare metadata
        $metadata = [
            'viewer' => [
                'id' => $viewer->id(),
                'username' => $viewer->username()->value(),
                'agent_type' => $viewer->agentType()->value(),
                'hierarchy_level' => $viewer->getHierarchyLevel(),
            ],
            'filters' => [
                'agent_type' => $command->getAgentType(),
                'direct_only' => $command->isDirectOnly(),
            ],
        ];

        return AgentListResponse::create(
            $agentResponses,
            $total,
            $command->getPage(),
            $command->getPerPage(),
            $metadata
        );
    }

    /**
     * Get agents that the viewer can see
     */
    private function getAgentsForViewer(Agent $viewer, GetAgentsCommand $command): array
    {
        // If targeting specific agent, get their downlines
        if ($command->getTargetAgentId() !== null) {
            $targetAgent = $this->agentRepository->findById($command->getTargetAgentId());
            if (! $targetAgent) {
                throw AgentException::notFound($command->getTargetAgentId());
            }

            // Check if viewer can drill down to this agent
            if (! $this->agentDomainService->canDrillDownTo($viewer, $targetAgent)) {
                throw AgentException::cannotDrillDown(
                    $viewer->username()->value(),
                    $targetAgent->username()->value()
                );
            }

            // Get downlines of the target agent
            return $command->isDirectOnly()
                ? $this->agentRepository->getDirectDownlines($targetAgent->id())
                : $this->agentRepository->getHierarchyDownlines($targetAgent->id());
        }

        // Get viewer's own downlines
        return $command->isDirectOnly()
            ? $this->agentRepository->getDirectDownlines($viewer->id())
            : $this->agentRepository->getHierarchyDownlines($viewer->id());
    }

    /**
     * Get total count for pagination
     */
    private function getTotalCount(Agent $viewer, GetAgentsCommand $command): int
    {
        // For now, return the count based on the actual query
        // In a real implementation, you'd have a separate count query
        $agents = $this->getAgentsForViewer($viewer, $command);

        return count($agents);
    }

    /**
     * Get permissions for agent based on viewer's relationship
     */
    private function getAgentPermissions(Agent $viewer, Agent $agent): array
    {
        $permissions = [];

        // Can view details
        $permissions['can_view'] = true;

        // Can manage (edit/delete)
        $permissions['can_manage'] = $viewer->canManage($agent);

        // Can drill down (view their downlines)
        $permissions['can_drill_down'] = $this->agentDomainService->canDrillDownTo($viewer, $agent);

        // Can create sub-agents
        $permissions['can_create_sub_agents'] = $viewer->canManage($agent) && $agent->agentType()->canManageSubAgents();

        // Available agent types they can create
        $permissions['can_create_types'] = [];
        if ($permissions['can_create_sub_agents']) {
            $permissions['can_create_types'] = $this->getCreatableAgentTypes($agent);
        }

        return $permissions;
    }

    /**
     * Get agent types that can be created under given agent
     */
    private function getCreatableAgentTypes(Agent $agent): array
    {
        $creatableTypes = [];
        $currentLevel = $agent->getHierarchyLevel();

        // Each agent can create agents one level below
        switch ($agent->agentType()->value()) {
            case AgentType::COMPANY:
                $creatableTypes[] = AgentType::SUPER_SENIOR;
                break;
            case AgentType::SUPER_SENIOR:
                $creatableTypes[] = AgentType::SENIOR;
                break;
            case AgentType::SENIOR:
                $creatableTypes[] = AgentType::MASTER;
                break;
            case AgentType::MASTER:
                $creatableTypes[] = AgentType::AGENT;
                break;
            case AgentType::AGENT:
                $creatableTypes[] = AgentType::MEMBER;
                break;
        }

        return $creatableTypes;
    }
}

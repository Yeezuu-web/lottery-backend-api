<?php

declare(strict_types=1);

namespace App\Application\Agent\UseCases;

use App\Application\Agent\Commands\CreateAgentCommand;
use App\Application\Agent\Responses\AgentResponse;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Agent\Events\AgentCreated;
use App\Domain\Agent\Exceptions\AgentException;
use App\Domain\Agent\Models\Agent;
use App\Domain\Agent\Services\AgentDomainService;
use App\Domain\Agent\ValueObjects\AgentType;

final readonly class CreateAgentUseCase
{
    public function __construct(
        private AgentRepositoryInterface $agentRepository,
        private AgentDomainService $agentDomainService
    ) {}

    public function execute(CreateAgentCommand $command): AgentResponse
    {
        // Find the creator agent
        $creator = $this->agentRepository->findById($command->getCreatorId());
        if (! $creator instanceof Agent) {
            throw AgentException::notFound($command->getCreatorId());
        }

        // Check if creator is active
        if (! $creator->isActive()) {
            throw AgentException::agentInactive($creator->username()->value());
        }

        // Validate agent creation
        $targetType = new AgentType($command->getAgentType());
        $this->agentDomainService->validateAgentCreation(
            $creator,
            $targetType,
            $command->getUsername()
        );

        // Validate email uniqueness
        if ($this->agentRepository->emailExists($command->getEmail())) {
            throw AgentException::emailAlreadyExists($command->getEmail());
        }

        // Determine upline ID
        $uplineId = $this->determineUplineId($creator, $targetType, $command->getUplineId());

        // Create the agent
        $agent = Agent::create(
            0, // Will be set by repository
            $command->getUsername(),
            $command->getAgentType(),
            $uplineId,
            $command->getName(),
            $command->getEmail(),
            true, // Active by default
            null, // Created at
            null, // Updated at
            $command->getPassword()
        );

        // Validate business rules
        $this->agentDomainService->validateBusinessRules($agent);

        // Save the agent
        $savedAgent = $this->agentRepository->save($agent);

        // Dispatch domain event for wallet creation
        event(AgentCreated::now($savedAgent));

        // Get agent statistics
        $statistics = $this->agentDomainService->getAgentStatistics($savedAgent);

        // Return response
        return AgentResponse::fromDomain($savedAgent, [], $statistics);
    }

    /**
     * Determine the upline ID for the new agent
     */
    private function determineUplineId(Agent $creator, AgentType $targetType, ?int $providedUplineId): ?int
    {
        // Company agents don't have upline
        if ($targetType->isCompany()) {
            return null;
        }

        // If upline is provided, validate it
        if ($providedUplineId !== null) {
            $upline = $this->agentRepository->findById($providedUplineId);
            if (! $upline instanceof Agent) {
                throw AgentException::uplineNotFound($providedUplineId);
            }

            // Check if creator can manage this upline relationship
            if (! $creator->canManage($upline)) {
                throw AgentException::cannotManageAgent(
                    $creator->username()->value(),
                    $upline->username()->value()
                );
            }

            return $providedUplineId;
        }

        // Default: creator becomes the upline
        return $creator->id();
    }
}

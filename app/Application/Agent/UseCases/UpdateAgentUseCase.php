<?php

declare(strict_types=1);

namespace App\Application\Agent\UseCases;

use App\Application\Agent\Commands\UpdateAgentCommand;
use App\Application\Agent\Responses\AgentResponse;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Agent\Exceptions\AgentException;
use App\Domain\Agent\Models\Agent;
use App\Domain\Agent\Services\AgentDomainService;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Hash;

final readonly class UpdateAgentUseCase
{
    public function __construct(
        private AgentRepositoryInterface $agentRepository,
        private AgentDomainService $agentDomainService
    ) {}

    public function execute(UpdateAgentCommand $command): AgentResponse
    {
        // Find the agent to update
        $agent = $this->agentRepository->findById($command->getId());
        if (! $agent instanceof Agent) {
            throw (new AgentException)->notFound($command->getId());
        }

        // Find the updator
        $updator = $this->agentRepository->findById($command->getUpdatorId());
        if (! $updator instanceof Agent) {
            throw (new AgentException)->notFound($command->getUpdatorId());
        }

        // Validate business rules
        $this->agentDomainService->validateBusinessRules($agent);

        $this->agentDomainService->validateManagementPermission($updator, $agent);

        // If email is provided, validate email uniqueness
        if ($command->getEmail() && $this->agentRepository->emailExists($command->getEmail())) {
            throw (new AgentException)->emailAlreadyExists($command->getEmail());
        }

        // If password is provided, hash it
        $password = null;
        if (! in_array($command->getPassword(), [null, '', '0'], true)) {
            $password = Hash::make($command->getPassword());
        }

        $updateData = Agent::update(
            $agent->id(),
            $agent->username()->value(),
            $agent->agentType()->value(),
            $agent->uplineId(),
            $command->getName() ?? $agent->name(),
            $command->getEmail() ?? $agent->email(),
            in_array($command->getPassword(), [null, '', '0'], true) ? $agent->password() : Hash::make($command->getPassword()),
            $agent->isActive(),
            $agent->createdAt() instanceof DateTimeImmutable ? $agent->createdAt()->format('Y-m-d H:i:s') : null,
            now(new DateTimeZone('Asia/Phnom_Penh'))->format('Y-m-d H:i:s'),
        );

        // Update the agent
        $agent = $this->agentRepository->save($updateData);

        // Get agent statistics
        $statistics = $this->agentDomainService->getAgentStatistics($agent);

        // Return response
        return AgentResponse::fromDomain($agent, [], $statistics);
    }
}

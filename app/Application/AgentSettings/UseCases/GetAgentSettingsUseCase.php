<?php

declare(strict_types=1);

namespace App\Application\AgentSettings\UseCases;

use App\Application\AgentSettings\Contracts\AgentSettingsRepositoryInterface;
use App\Application\AgentSettings\Queries\GetAgentSettingsQuery;
use App\Application\AgentSettings\Responses\AgentSettingsOperationResponse;
use App\Application\AgentSettings\Responses\AgentSettingsResponse;
use App\Domain\AgentSettings\Exceptions\AgentSettingsException;
use Exception;

final readonly class GetAgentSettingsUseCase
{
    public function __construct(
        private AgentSettingsRepositoryInterface $repository
    ) {}

    public function execute(GetAgentSettingsQuery $query): AgentSettingsOperationResponse
    {
        try {
            // Get agent settings
            $agentSettings = $this->repository->findByAgentId($query->agentId);

            // Check if agent settings exist
            if (! $agentSettings instanceof \App\Domain\AgentSettings\Models\AgentSettings) {
                throw AgentSettingsException::notFound($query->agentId);
            }

            // Get usage data if requested
            $dailyUsage = null;
            $numberUsage = [];

            if ($query->includeUsage ?? true) {
                $dailyUsage = $this->repository->getDailyUsage($query->agentId);
                $numberUsage = $this->repository->getNumberUsage($query->agentId);
            }

            // Create response with usage data
            $response = AgentSettingsResponse::fromDomainWithUsage(
                $agentSettings,
                $dailyUsage,
                $numberUsage
            );

            return AgentSettingsOperationResponse::success(
                message: 'Agent settings retrieved successfully',
                data: $response
            );
        } catch (AgentSettingsException $e) {
            return AgentSettingsOperationResponse::error($e->getMessage());
        } catch (Exception $e) {
            return AgentSettingsOperationResponse::error(
                'Failed to retrieve agent settings: '.$e->getMessage()
            );
        }
    }
}

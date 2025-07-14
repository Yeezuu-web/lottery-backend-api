<?php

namespace App\Application\AgentSettings\UseCases;

use App\Application\AgentSettings\Contracts\AgentSettingsRepositoryInterface;
use App\Application\AgentSettings\Queries\GetAgentSettingsQuery;
use App\Application\AgentSettings\Responses\AgentSettingsOperationResponse;
use App\Application\AgentSettings\Responses\AgentSettingsResponse;
use App\Domain\AgentSettings\Exceptions\AgentSettingsException;

final class GetAgentSettingsUseCase
{
    public function __construct(
        private readonly AgentSettingsRepositoryInterface $repository
    ) {}

    public function execute(GetAgentSettingsQuery $query): AgentSettingsOperationResponse
    {
        try {
            // Check if we need to refresh cache
            if ($query->refreshCache) {
                $agentSettings = $this->repository->refreshCache($query->agentId);
            } else {
                $agentSettings = $this->repository->findByAgentId($query->agentId);
            }

            // Check if agent settings exist
            if ($agentSettings === null) {
                throw AgentSettingsException::notFound($query->agentId);
            }

            // Check if cache is expired and needs refresh
            if ($agentSettings->isCacheExpired()) {
                $agentSettings = $this->repository->refreshCache($query->agentId);
            }

            // Return success response
            return AgentSettingsOperationResponse::success(
                message: 'Agent settings retrieved successfully',
                data: AgentSettingsResponse::fromDomain($agentSettings)
            );
        } catch (AgentSettingsException $e) {
            return AgentSettingsOperationResponse::failure(
                message: $e->getMessage(),
                errors: ['agent_settings' => $e->getMessage()]
            );
        } catch (\Exception $e) {
            return AgentSettingsOperationResponse::failure(
                message: 'Failed to retrieve agent settings',
                errors: ['system' => $e->getMessage()]
            );
        }
    }
}

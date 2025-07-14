<?php

namespace App\Application\AgentSettings\UseCases;

use App\Application\AgentSettings\Commands\UpdateSharingRateCommand;
use App\Application\AgentSettings\Contracts\AgentSettingsRepositoryInterface;
use App\Application\AgentSettings\Responses\AgentSettingsOperationResponse;
use App\Application\AgentSettings\Responses\AgentSettingsResponse;
use App\Domain\AgentSettings\Exceptions\AgentSettingsException;

final class UpdateSharingRateUseCase
{
    public function __construct(
        private readonly AgentSettingsRepositoryInterface $repository
    ) {}

    public function execute(UpdateSharingRateCommand $command): AgentSettingsOperationResponse
    {
        try {
            // Get existing agent settings
            $agentSettings = $this->repository->findByAgentId($command->agentId);

            if ($agentSettings === null) {
                throw AgentSettingsException::notFound($command->agentId);
            }

            // Update sharing rate only
            $agentSettings = $agentSettings->updateSharingRate($command->sharingRate);

            // Save updated settings
            $savedSettings = $this->repository->save($agentSettings);

            // Return success response
            return AgentSettingsOperationResponse::success(
                message: 'Sharing rate updated successfully',
                data: AgentSettingsResponse::fromDomain($savedSettings)
            );
        } catch (AgentSettingsException $e) {
            return AgentSettingsOperationResponse::failure(
                message: $e->getMessage(),
                errors: ['sharing_rate' => $e->getMessage()]
            );
        } catch (\Exception $e) {
            return AgentSettingsOperationResponse::failure(
                message: 'Failed to update sharing rate',
                errors: ['system' => $e->getMessage()]
            );
        }
    }
}

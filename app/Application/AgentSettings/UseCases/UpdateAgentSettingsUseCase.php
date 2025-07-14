<?php

namespace App\Application\AgentSettings\UseCases;

use App\Application\AgentSettings\Commands\UpdateAgentSettingsCommand;
use App\Application\AgentSettings\Contracts\AgentSettingsRepositoryInterface;
use App\Application\AgentSettings\Responses\AgentSettingsOperationResponse;
use App\Application\AgentSettings\Responses\AgentSettingsResponse;
use App\Domain\AgentSettings\Exceptions\AgentSettingsException;
use App\Domain\AgentSettings\ValueObjects\PayoutProfile;

final class UpdateAgentSettingsUseCase
{
    public function __construct(
        private readonly AgentSettingsRepositoryInterface $repository
    ) {}

    public function execute(UpdateAgentSettingsCommand $command): AgentSettingsOperationResponse
    {
        try {
            // Get existing agent settings
            $agentSettings = $this->repository->findByAgentId($command->agentId);

            if ($agentSettings === null) {
                throw AgentSettingsException::notFound($command->agentId);
            }

            // Update payout profile if provided
            if ($command->payoutProfile !== null) {
                $payoutProfile = PayoutProfile::fromArray($command->payoutProfile);
                $agentSettings = $agentSettings->updatePayoutProfile($payoutProfile);
            }

            // Update commission and sharing rates if provided
            if ($command->commissionRate !== null || $command->sharingRate !== null) {
                $agentSettings = $agentSettings->updateCommissionSharingRates(
                    $command->commissionRate,
                    $command->sharingRate
                );
            }

            // Update other settings as needed
            // Note: For simplicity, we're only handling commission/sharing/payout profile updates
            // Additional properties like betting limits, blocked numbers, etc. would need similar handling

            // Save updated settings
            $savedSettings = $this->repository->save($agentSettings);

            // Return success response
            return AgentSettingsOperationResponse::success(
                message: 'Agent settings updated successfully',
                data: AgentSettingsResponse::fromDomain($savedSettings)
            );
        } catch (AgentSettingsException $e) {
            return AgentSettingsOperationResponse::failure(
                message: $e->getMessage(),
                errors: ['agent_settings' => $e->getMessage()]
            );
        } catch (\Exception $e) {
            return AgentSettingsOperationResponse::failure(
                message: 'Failed to update agent settings',
                errors: ['system' => $e->getMessage()]
            );
        }
    }
}

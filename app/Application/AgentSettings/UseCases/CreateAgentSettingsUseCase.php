<?php

declare(strict_types=1);

namespace App\Application\AgentSettings\UseCases;

use App\Application\AgentSettings\Commands\CreateAgentSettingsCommand;
use App\Application\AgentSettings\Contracts\AgentSettingsRepositoryInterface;
use App\Application\AgentSettings\Responses\AgentSettingsOperationResponse;
use App\Application\AgentSettings\Responses\AgentSettingsResponse;
use App\Domain\AgentSettings\Exceptions\AgentSettingsException;
use App\Domain\AgentSettings\Models\AgentSettings;
use App\Domain\AgentSettings\ValueObjects\PayoutProfile;
use Exception;

final readonly class CreateAgentSettingsUseCase
{
    public function __construct(
        private AgentSettingsRepositoryInterface $repository
    ) {}

    public function execute(CreateAgentSettingsCommand $command): AgentSettingsOperationResponse
    {
        try {
            // Check if agent settings already exist
            if ($this->repository->exists($command->agentId)) {
                throw AgentSettingsException::alreadyExists($command->agentId);
            }

            // Create agent settings based on provided data
            $agentSettings = $this->createAgentSettings($command);

            // Save to repository
            $savedSettings = $this->repository->save($agentSettings);

            // Return success response
            return AgentSettingsOperationResponse::success(
                message: 'Agent settings created successfully',
                data: AgentSettingsResponse::fromDomain($savedSettings)
            );
        } catch (AgentSettingsException $e) {
            return AgentSettingsOperationResponse::failure(
                message: $e->getMessage(),
                errors: ['agent_settings' => $e->getMessage()]
            );
        } catch (Exception $e) {
            return AgentSettingsOperationResponse::failure(
                message: 'Failed to create agent settings',
                errors: ['system' => $e->getMessage()]
            );
        }
    }

    private function createAgentSettings(CreateAgentSettingsCommand $command): AgentSettings
    {
        // If custom payout profile is provided, use it
        if ($command->payoutProfile !== null) {
            $payoutProfile = PayoutProfile::fromArray($command->payoutProfile);

            return AgentSettings::createWithCustomProfile(
                agentId: $command->agentId,
                payoutProfile: $payoutProfile,
                commissionRate: $command->commissionRate,
                sharingRate: $command->sharingRate
            );
        }

        // Otherwise create with default profile
        $agentSettings = AgentSettings::createDefault($command->agentId);

        // If commission or sharing rates are provided, update them
        if ($command->commissionRate !== null || $command->sharingRate !== null) {
            return $agentSettings->updateCommissionSharingRates(
                $command->commissionRate,
                $command->sharingRate
            );
        }

        return $agentSettings;
    }
}

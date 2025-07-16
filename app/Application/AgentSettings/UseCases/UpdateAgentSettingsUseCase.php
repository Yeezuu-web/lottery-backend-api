<?php

declare(strict_types=1);

namespace App\Application\AgentSettings\UseCases;

use App\Application\AgentSettings\Commands\UpdateAgentSettingsCommand;
use App\Application\AgentSettings\Contracts\AgentSettingsRepositoryInterface;
use App\Application\AgentSettings\Responses\AgentSettingsOperationResponse;
use App\Application\AgentSettings\Responses\AgentSettingsResponse;
use App\Domain\AgentSettings\Exceptions\AgentSettingsException;
use App\Domain\AgentSettings\ValueObjects\DailyLimit;
use App\Domain\AgentSettings\ValueObjects\NumberLimit;
use Exception;

final readonly class UpdateAgentSettingsUseCase
{
    public function __construct(
        private AgentSettingsRepositoryInterface $repository
    ) {}

    public function execute(UpdateAgentSettingsCommand $command): AgentSettingsOperationResponse
    {
        try {
            // Get existing agent settings
            $agentSettings = $this->repository->findByAgentId($command->agentId);

            if (! $agentSettings instanceof \App\Domain\AgentSettings\Models\AgentSettings) {
                throw AgentSettingsException::notFound($command->agentId);
            }

            // Update daily limit if provided
            if ($command->dailyLimit !== null) {
                $dailyLimit = DailyLimit::create($command->dailyLimit);
                $agentSettings = $agentSettings->updateDailyLimit($dailyLimit);
            }

            // Update max commission if provided
            if ($command->maxCommission !== null) {
                $agentSettings = $agentSettings->updateMaxCommission($command->maxCommission);
            }

            // Update max share if provided
            if ($command->maxShare !== null) {
                $agentSettings = $agentSettings->updateMaxShare($command->maxShare);
            }

            // Update number limits if provided
            if ($command->numberLimits !== null) {
                $numberLimits = [];
                foreach ($command->numberLimits as $gameType => $limits) {
                    foreach ($limits as $number => $limit) {
                        $numberLimits[] = NumberLimit::create($gameType, (string) $number, (int) $limit);
                    }
                }

                $agentSettings = $agentSettings->updateNumberLimits($numberLimits);
            }

            // Update blocked numbers if provided
            if ($command->blockedNumbers !== null) {
                $agentSettings = $agentSettings->updateBlockedNumbers($command->blockedNumbers);
            }

            // Save updated settings
            $savedSettings = $this->repository->save($agentSettings);

            // Return success response
            return AgentSettingsOperationResponse::success(
                message: 'Agent settings updated successfully',
                data: AgentSettingsResponse::fromDomain($savedSettings)
            );
        } catch (AgentSettingsException $e) {
            return AgentSettingsOperationResponse::error($e->getMessage());
        } catch (Exception $e) {
            return AgentSettingsOperationResponse::error(
                'Failed to update agent settings: '.$e->getMessage()
            );
        }
    }
}

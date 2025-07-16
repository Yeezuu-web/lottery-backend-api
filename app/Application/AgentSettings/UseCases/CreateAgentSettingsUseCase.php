<?php

declare(strict_types=1);

namespace App\Application\AgentSettings\UseCases;

use App\Application\AgentSettings\Commands\CreateAgentSettingsCommand;
use App\Application\AgentSettings\Contracts\AgentSettingsRepositoryInterface;
use App\Application\AgentSettings\Responses\AgentSettingsOperationResponse;
use App\Application\AgentSettings\Responses\AgentSettingsResponse;
use App\Domain\AgentSettings\Exceptions\AgentSettingsException;
use App\Domain\AgentSettings\Models\AgentSettings;
use App\Domain\AgentSettings\ValueObjects\DailyLimit;
use App\Domain\AgentSettings\ValueObjects\NumberLimit;
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
            if ($this->repository->hasSettings($command->agentId)) {
                throw AgentSettingsException::alreadyExists($command->agentId);
            }

            // Create daily limit value object
            $dailyLimit = DailyLimit::create($command->dailyLimit);

            // Create number limits value objects
            $numberLimits = [];
            foreach ($command->numberLimits as $gameType => $limits) {
                foreach ($limits as $number => $limit) {
                    $numberLimits[] = NumberLimit::create($gameType, (string) $number, (int) $limit);
                }
            }

            // Create agent settings
            $agentSettings = AgentSettings::create(
                agentId: $command->agentId,
                dailyLimit: $dailyLimit,
                maxCommission: $command->maxCommission,
                maxShare: $command->maxShare,
                numberLimits: $numberLimits,
                blockedNumbers: $command->blockedNumbers
            );

            // Save to repository
            $savedSettings = $this->repository->save($agentSettings);

            // Return success response
            return AgentSettingsOperationResponse::success(
                message: 'Agent settings created successfully',
                data: AgentSettingsResponse::fromDomain($savedSettings)
            );
        } catch (AgentSettingsException $e) {
            return AgentSettingsOperationResponse::error($e->getMessage());
        } catch (Exception $e) {
            return AgentSettingsOperationResponse::error(
                'Failed to create agent settings: '.$e->getMessage()
            );
        }
    }
}

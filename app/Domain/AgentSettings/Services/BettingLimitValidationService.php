<?php

declare(strict_types=1);

namespace App\Domain\AgentSettings\Services;

use App\Application\AgentSettings\Contracts\AgentSettingsRepositoryInterface;
use App\Domain\AgentSettings\Contracts\BettingLimitValidationServiceInterface;
use App\Domain\AgentSettings\Exceptions\AgentSettingsException;
use App\Domain\AgentSettings\Models\AgentSettings;

final readonly class BettingLimitValidationService implements BettingLimitValidationServiceInterface
{
    public function __construct(
        private AgentSettingsRepositoryInterface $repository
    ) {}

    /**
     * Validate daily limit for agent before processing bet
     */
    public function validateDailyLimit(int $agentId, int $betAmount): void
    {
        $agentSettings = $this->getAgentSettings($agentId);

        // If no agent settings found, skip validation
        if (! $agentSettings instanceof AgentSettings) {
            return;
        }

        $dailyLimit = $agentSettings->getDailyLimit();

        // If unlimited, no validation needed
        if ($dailyLimit->isUnlimited()) {
            return;
        }

        $currentUsage = $this->repository->getDailyUsage($agentId);
        $newTotal = $currentUsage + $betAmount;

        if ($newTotal > $dailyLimit->getLimit()) {
            throw AgentSettingsException::dailyLimitExceeded(
                $agentId,
                $dailyLimit->getLimit(),
                $currentUsage,
                $betAmount
            );
        }
    }

    /**
     * Validate number limit for specific game type and number
     */
    public function validateNumberLimit(int $agentId, string $gameType, string $number, int $betAmount): void
    {
        $agentSettings = $this->getAgentSettings($agentId);

        // If no agent settings found, skip validation
        if (! $agentSettings instanceof AgentSettings) {
            return;
        }

        $numberLimits = $agentSettings->getNumberLimits();

        // Find specific number limit for this game type and number
        $specificLimit = null;
        foreach ($numberLimits as $numberLimit) {
            if ($numberLimit->getGameType() === $gameType && $numberLimit->getNumber() === $number) {
                $specificLimit = $numberLimit;
                break;
            }
        }

        // If no specific limit set, no validation needed
        if ($specificLimit === null) {
            return;
        }

        $currentUsage = $this->repository->getNumberUsage($agentId)[$gameType][$number] ?? 0;
        $newTotal = $currentUsage + $betAmount;

        if ($newTotal > $specificLimit->getLimit()) {
            throw AgentSettingsException::numberLimitExceeded(
                $agentId,
                $gameType,
                $number,
                $specificLimit->getLimit(),
                $currentUsage,
                $betAmount
            );
        }
    }

    /**
     * Validate blocked numbers
     */
    public function validateBlockedNumbers(int $agentId, array $numbers): void
    {
        $agentSettings = $this->getAgentSettings($agentId);

        // If no agent settings found, skip validation
        if (! $agentSettings instanceof AgentSettings) {
            return;
        }

        $blockedNumbers = $agentSettings->getBlockedNumbers();

        foreach ($numbers as $number) {
            if (in_array($number, $blockedNumbers, true)) {
                throw AgentSettingsException::numberBlocked($agentId, $number);
            }
        }
    }

    /**
     * Comprehensive validation for bet processing
     */
    public function validateBet(int $agentId, string $gameType, array $numbers, int $totalAmount): void
    {
        // Validate daily limit
        $this->validateDailyLimit($agentId, $totalAmount);

        // Validate blocked numbers
        $this->validateBlockedNumbers($agentId, $numbers);

        // Validate number limits for each number
        $amountPerNumber = $totalAmount / count($numbers);
        foreach ($numbers as $number) {
            $this->validateNumberLimit($agentId, $gameType, $number, (int) $amountPerNumber);
        }
    }

    /**
     * Record usage after successful bet processing
     */
    public function recordUsage(int $agentId, string $gameType, array $numbers, int $totalAmount): void
    {
        // Record daily usage
        $this->repository->incrementDailyUsage($agentId, $totalAmount);

        // Record number usage
        $amountPerNumber = $totalAmount / count($numbers);
        foreach ($numbers as $number) {
            $this->repository->incrementNumberUsage($agentId, $gameType, $number, (int) $amountPerNumber);
        }
    }

    private function getAgentSettings(int $agentId): ?AgentSettings
    {
        return $this->repository->findByAgentId($agentId);
    }
}

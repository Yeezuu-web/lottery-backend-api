<?php

declare(strict_types=1);

namespace App\Domain\AgentSettings\Contracts;

interface BettingLimitValidationServiceInterface
{
    /**
     * Validate daily limit for agent before processing bet
     */
    public function validateDailyLimit(int $agentId, int $betAmount): void;

    /**
     * Validate number limit for specific game type and number
     */
    public function validateNumberLimit(int $agentId, string $gameType, string $number, int $betAmount): void;

    /**
     * Validate blocked numbers
     */
    public function validateBlockedNumbers(int $agentId, array $numbers): void;

    /**
     * Comprehensive validation for bet processing
     */
    public function validateBet(int $agentId, string $gameType, array $numbers, int $totalAmount): void;

    /**
     * Record usage after successful bet processing
     */
    public function recordUsage(int $agentId, string $gameType, array $numbers, int $totalAmount): void;
}

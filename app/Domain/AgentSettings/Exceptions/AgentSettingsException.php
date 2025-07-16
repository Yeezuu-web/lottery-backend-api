<?php

declare(strict_types=1);

namespace App\Domain\AgentSettings\Exceptions;

use App\Shared\Exceptions\DomainException;

final class AgentSettingsException extends DomainException
{
    public static function settingsNotFound(int $agentId): self
    {
        return new self('Agent settings not found for agent ID '.$agentId);
    }

    public static function notFound(int $agentId): self
    {
        return new self('Agent settings not found for agent ID '.$agentId);
    }

    public static function alreadyExists(int $agentId): self
    {
        return new self('Agent settings already exist for agent ID '.$agentId);
    }

    public static function invalidPayoutProfile(string $reason): self
    {
        return new self('Invalid payout profile: '.$reason);
    }

    public static function commissionSharingExceedsLimit(float $total, float $maxAllowed): self
    {
        return new self(
            sprintf(
                'Commission and sharing total (%.2f%%) exceeds maximum allowed (%.2f%%)',
                $total,
                $maxAllowed
            )
        );
    }

    public static function invalidCommissionRate(float $rate): self
    {
        return new self(sprintf('Invalid commission rate: %s%%. Must be between 0 and 100', $rate));
    }

    public static function invalidSharingRate(float $rate): self
    {
        return new self(sprintf('Invalid sharing rate: %s%%. Must be between 0 and 100', $rate));
    }

    public static function invalidDailyLimit(float $amount): self
    {
        return new self(sprintf('Invalid daily limit: %.2f KHR. Must be positive', $amount));
    }

    public static function invalidGameType(string $gameType): self
    {
        return new self(sprintf('Invalid game type: %s. Must be 2D or 3D', $gameType));
    }

    public static function invalidNumberLimits(string $gameType): self
    {
        return new self(sprintf('Invalid number limits for game type: %s. Must be array', $gameType));
    }

    public static function invalidNumberLimit(string $gameType, string $number, mixed $limit): self
    {
        return new self(
            sprintf(
                'Invalid number limit for %s number %s: %s. Must be positive number',
                $gameType,
                $number,
                $limit
            )
        );
    }

    public static function dailyLimitExceeded(int $agentId, int $limit, int $currentUsage, int $betAmount): self
    {
        return new self(
            sprintf(
                'Daily limit exceeded for agent %d. Current usage: %d KHR, Bet amount: %d KHR, Limit: %d KHR',
                $agentId,
                $currentUsage,
                $betAmount,
                $limit
            )
        );
    }

    public static function numberLimitExceeded(int $agentId, string $gameType, string $number, int $limit, int $currentUsage, int $betAmount): self
    {
        return new self(
            sprintf(
                'Number limit exceeded for agent %d on %s number %s. Current usage: %d KHR, Bet amount: %d KHR, Limit: %d KHR',
                $agentId,
                $gameType,
                $number,
                $currentUsage,
                $betAmount,
                $limit
            )
        );
    }

    public static function numberBlocked(int $agentId, string $number): self
    {
        return new self(sprintf('Number %s is blocked for agent %d', $number, $agentId));
    }

    public static function cannotInheritFromInactiveAgent(int $sourceAgentId): self
    {
        return new self('Cannot inherit settings from inactive agent ID '.$sourceAgentId);
    }

    public static function circularInheritanceDetected(int $agentId, array $hierarchyPath): self
    {
        $pathString = implode(' → ', $hierarchyPath);

        return new self(sprintf('Circular inheritance detected for agent ID %d: %s', $agentId, $pathString));
    }

    public static function uplineSettingsNotFound(int $uplineId): self
    {
        return new self('Upline settings not found for agent ID '.$uplineId);
    }

    public static function cannotExceedUplineCommission(float $requested, float $uplineMax): self
    {
        return new self(
            sprintf(
                'Commission rate (%.2f%%) cannot exceed upline maximum (%.2f%%)',
                $requested,
                $uplineMax
            )
        );
    }

    public static function settingsComputationFailed(int $agentId, string $reason): self
    {
        return new self(sprintf('Failed to compute settings for agent ID %d: %s', $agentId, $reason));
    }

    public static function cacheExpired(int $agentId): self
    {
        return new self('Settings cache expired for agent ID '.$agentId);
    }

    public static function invalidBettingLimit(string $gameType, array $limit): self
    {
        return new self(sprintf('Invalid betting limit for %s: ', $gameType).json_encode($limit));
    }

    public static function blockedNumberInvalid(string $number): self
    {
        return new self('Invalid blocked number format: '.$number);
    }

    public static function settingsUpdateFailed(int $agentId, string $reason): self
    {
        return new self(sprintf('Failed to update settings for agent ID %d: %s', $agentId, $reason));
    }

    public static function hierarchyDepthExceeded(int $maxDepth): self
    {
        return new self(sprintf('Maximum hierarchy depth of %d levels exceeded', $maxDepth));
    }

    public static function templateNotFound(string $templateName): self
    {
        return new self(sprintf("Payout profile template '%s' not found", $templateName));
    }

    public static function inactiveTemplate(string $templateName): self
    {
        return new self(sprintf("Payout profile template '%s' is inactive", $templateName));
    }
}

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

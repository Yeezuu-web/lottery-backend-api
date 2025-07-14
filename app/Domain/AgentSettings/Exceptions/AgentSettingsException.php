<?php

namespace App\Domain\AgentSettings\Exceptions;

use App\Shared\Exceptions\DomainException;

class AgentSettingsException extends DomainException
{
    public static function settingsNotFound(int $agentId): self
    {
        return new self("Agent settings not found for agent ID {$agentId}");
    }

    public static function notFound(int $agentId): self
    {
        return new self("Agent settings not found for agent ID {$agentId}");
    }

    public static function alreadyExists(int $agentId): self
    {
        return new self("Agent settings already exist for agent ID {$agentId}");
    }

    public static function invalidPayoutProfile(string $reason): self
    {
        return new self("Invalid payout profile: {$reason}");
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
        return new self("Invalid commission rate: {$rate}%. Must be between 0 and 100");
    }

    public static function invalidSharingRate(float $rate): self
    {
        return new self("Invalid sharing rate: {$rate}%. Must be between 0 and 100");
    }

    public static function cannotInheritFromInactiveAgent(int $sourceAgentId): self
    {
        return new self("Cannot inherit settings from inactive agent ID {$sourceAgentId}");
    }

    public static function circularInheritanceDetected(int $agentId, array $hierarchyPath): self
    {
        $pathString = implode(' → ', $hierarchyPath);

        return new self("Circular inheritance detected for agent ID {$agentId}: {$pathString}");
    }

    public static function uplineSettingsNotFound(int $uplineId): self
    {
        return new self("Upline settings not found for agent ID {$uplineId}");
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
        return new self("Failed to compute settings for agent ID {$agentId}: {$reason}");
    }

    public static function cacheExpired(int $agentId): self
    {
        return new self("Settings cache expired for agent ID {$agentId}");
    }

    public static function invalidBettingLimit(string $gameType, array $limit): self
    {
        return new self("Invalid betting limit for {$gameType}: ".json_encode($limit));
    }

    public static function blockedNumberInvalid(string $number): self
    {
        return new self("Invalid blocked number format: {$number}");
    }

    public static function settingsUpdateFailed(int $agentId, string $reason): self
    {
        return new self("Failed to update settings for agent ID {$agentId}: {$reason}");
    }

    public static function hierarchyDepthExceeded(int $maxDepth): self
    {
        return new self("Maximum hierarchy depth of {$maxDepth} levels exceeded");
    }

    public static function templateNotFound(string $templateName): self
    {
        return new self("Payout profile template '{$templateName}' not found");
    }

    public static function inactiveTemplate(string $templateName): self
    {
        return new self("Payout profile template '{$templateName}' is inactive");
    }
}

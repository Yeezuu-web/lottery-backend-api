<?php

declare(strict_types=1);

namespace App\Domain\AgentSettings\Models;

use App\Domain\AgentSettings\Exceptions\AgentSettingsException;
use App\Domain\AgentSettings\ValueObjects\CommissionSharingSettings;
use App\Domain\AgentSettings\ValueObjects\PayoutProfile;
use Carbon\Carbon;

final readonly class AgentSettings
{
    // Add creation and update timestamps
    private Carbon $createdAt;

    private Carbon $updatedAt;

    public function __construct(
        private int $agentId,
        private ?PayoutProfile $payoutProfile,
        private ?int $payoutProfileSourceAgentId,
        private bool $hasCustomPayoutProfile,
        private CommissionSharingSettings $commissionSharingSettings,
        private PayoutProfile $effectivePayoutProfile,
        private int $effectivePayoutSourceAgentId,
        private CommissionSharingSettings $effectiveCommissionSharingSettings,
        private bool $isComputed = false,
        private ?Carbon $computedAt = null,
        private ?Carbon $cacheExpiresAt = null,
        private array $bettingLimits = [],
        private array $blockedNumbers = [],
        private bool $autoSettlement = false,
        private bool $isActive = true,
        ?Carbon $createdAt = null,
        ?Carbon $updatedAt = null
    ) {
        $this->createdAt = $createdAt ?? Carbon::now();
        $this->updatedAt = $updatedAt ?? Carbon::now();
    }

    public static function createDefault(int $agentId): self
    {
        $defaultProfile = PayoutProfile::default();
        $defaultSettings = CommissionSharingSettings::fromPayoutProfile(5.0, 2.0, $defaultProfile);

        return new self(
            agentId: $agentId,
            payoutProfile: null,
            payoutProfileSourceAgentId: null,
            hasCustomPayoutProfile: false,
            commissionSharingSettings: $defaultSettings,
            effectivePayoutProfile: $defaultProfile,
            effectivePayoutSourceAgentId: $agentId,
            effectiveCommissionSharingSettings: $defaultSettings,
            isComputed: false,
            computedAt: Carbon::now(),
            cacheExpiresAt: Carbon::now()->addHours(24),
            isActive: true,
            createdAt: Carbon::now(),
            updatedAt: Carbon::now()
        );
    }

    public static function createWithCustomProfile(
        int $agentId,
        PayoutProfile $payoutProfile,
        ?float $commissionRate = null,
        ?float $sharingRate = null
    ): self {
        $settings = CommissionSharingSettings::fromPayoutProfile(
            $commissionRate,
            $sharingRate,
            $payoutProfile
        );

        return new self(
            agentId: $agentId,
            payoutProfile: $payoutProfile,
            payoutProfileSourceAgentId: $agentId,
            hasCustomPayoutProfile: true,
            commissionSharingSettings: $settings,
            effectivePayoutProfile: $payoutProfile,
            effectivePayoutSourceAgentId: $agentId,
            effectiveCommissionSharingSettings: $settings,
            isComputed: false,
            computedAt: Carbon::now(),
            cacheExpiresAt: Carbon::now()->addHours(24),
            isActive: true,
            createdAt: Carbon::now(),
            updatedAt: Carbon::now()
        );
    }

    public function updatePayoutProfile(PayoutProfile $newProfile): self
    {
        // Validate commission/sharing rates against new profile
        $maxAllowed = $newProfile->getMaxCommissionSharingRate();
        $currentTotal = $this->commissionSharingSettings->getTotalRate();

        if ($currentTotal > $maxAllowed) {
            throw AgentSettingsException::commissionSharingExceedsLimit($currentTotal, $maxAllowed);
        }

        $newSettings = CommissionSharingSettings::fromPayoutProfile(
            $this->commissionSharingSettings->getCommissionRateValue(),
            $this->commissionSharingSettings->getSharingRateValue(),
            $newProfile
        );

        return new self(
            agentId: $this->agentId,
            payoutProfile: $newProfile,
            payoutProfileSourceAgentId: $this->agentId,
            hasCustomPayoutProfile: true,
            commissionSharingSettings: $newSettings,
            effectivePayoutProfile: $newProfile,
            effectivePayoutSourceAgentId: $this->agentId,
            effectiveCommissionSharingSettings: $newSettings,
            isComputed: false,
            computedAt: Carbon::now(),
            cacheExpiresAt: Carbon::now()->addHours(24),
            bettingLimits: $this->bettingLimits,
            blockedNumbers: $this->blockedNumbers,
            autoSettlement: $this->autoSettlement,
            isActive: $this->isActive,
            createdAt: $this->createdAt,
            updatedAt: Carbon::now()
        );
    }

    public function updateCommissionSharingRates(?float $commissionRate = null, ?float $sharingRate = null): self
    {
        $newSettings = CommissionSharingSettings::fromPayoutProfile(
            $commissionRate,
            $sharingRate,
            $this->effectivePayoutProfile
        );

        return new self(
            agentId: $this->agentId,
            payoutProfile: $this->payoutProfile,
            payoutProfileSourceAgentId: $this->payoutProfileSourceAgentId,
            hasCustomPayoutProfile: $this->hasCustomPayoutProfile,
            commissionSharingSettings: $newSettings,
            effectivePayoutProfile: $this->effectivePayoutProfile,
            effectivePayoutSourceAgentId: $this->effectivePayoutSourceAgentId,
            effectiveCommissionSharingSettings: $newSettings,
            isComputed: $this->isComputed,
            computedAt: Carbon::now(),
            cacheExpiresAt: Carbon::now()->addHours(24),
            bettingLimits: $this->bettingLimits,
            blockedNumbers: $this->blockedNumbers,
            autoSettlement: $this->autoSettlement,
            isActive: $this->isActive,
            createdAt: $this->createdAt,
            updatedAt: Carbon::now()
        );
    }

    public function updateCommissionRate(?float $commissionRate): self
    {
        $currentSharing = $this->commissionSharingSettings->hasSharing()
            ? $this->commissionSharingSettings->getSharingRateValue()
            : null;

        return $this->updateCommissionSharingRates($commissionRate, $currentSharing);
    }

    public function updateSharingRate(?float $sharingRate): self
    {
        $currentCommission = $this->commissionSharingSettings->hasCommission()
            ? $this->commissionSharingSettings->getCommissionRateValue()
            : null;

        return $this->updateCommissionSharingRates($currentCommission, $sharingRate);
    }

    // === Getters ===

    public function getAgentId(): int
    {
        return $this->agentId;
    }

    public function getPayoutProfile(): ?PayoutProfile
    {
        return $this->payoutProfile;
    }

    public function getEffectivePayoutProfile(): PayoutProfile
    {
        return $this->effectivePayoutProfile;
    }

    public function getCommissionSharingSettings(): CommissionSharingSettings
    {
        return $this->commissionSharingSettings;
    }

    public function getEffectiveCommissionSharingSettings(): CommissionSharingSettings
    {
        return $this->effectiveCommissionSharingSettings;
    }

    public function hasCustomPayoutProfile(): bool
    {
        return $this->hasCustomPayoutProfile;
    }

    public function isComputed(): bool
    {
        return $this->isComputed;
    }

    public function isCacheExpired(): bool
    {
        return $this->cacheExpiresAt && now()->greaterThan($this->cacheExpiresAt);
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getComputedAt(): ?Carbon
    {
        return $this->computedAt;
    }

    public function getCacheExpiresAt(): ?Carbon
    {
        return $this->cacheExpiresAt;
    }

    public function getBettingLimits(): array
    {
        return $this->bettingLimits;
    }

    public function getBlockedNumbers(): array
    {
        return $this->blockedNumbers;
    }

    public function getAutoSettlement(): bool
    {
        return $this->autoSettlement;
    }

    public function getPayoutProfileSourceAgentId(): ?int
    {
        return $this->payoutProfileSourceAgentId;
    }

    public function getEffectivePayoutSourceAgentId(): int
    {
        return $this->effectivePayoutSourceAgentId;
    }

    public function getCommissionRate(): ?CommissionRate
    {
        return $this->commissionSharingSettings->getCommissionRate();
    }

    public function getSharingRate(): ?SharingRate
    {
        return $this->commissionSharingSettings->getSharingRate();
    }

    public function getMaxCommissionSharingRate(): float
    {
        return $this->commissionSharingSettings->getMaxCombinedRate();
    }

    public function getEffectiveCommissionRate(): ?CommissionRate
    {
        return $this->effectiveCommissionSharingSettings->getCommissionRate();
    }

    public function getEffectiveSharingRate(): ?SharingRate
    {
        return $this->effectiveCommissionSharingSettings->getSharingRate();
    }

    public function hasAutoSettlement(): bool
    {
        return $this->autoSettlement;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }

    // === Business Logic Methods ===

    public function calculatePayout(float $betAmount, string $gameType): float
    {
        $multiplier = $this->effectivePayoutProfile->getMultiplier($gameType);

        return $betAmount * $multiplier;
    }

    public function calculateCommission(float $turnover): float
    {
        return $this->effectiveCommissionSharingSettings->calculateCommissionAmount($turnover);
    }

    public function calculateSharing(float $turnover): float
    {
        return $this->effectiveCommissionSharingSettings->calculateSharingAmount($turnover);
    }

    public function getBettingLimit(string $gameType): ?array
    {
        return $this->bettingLimits[$gameType] ?? null;
    }

    public function isNumberBlocked(string $number): bool
    {
        return in_array($number, $this->blockedNumbers);
    }

    // === Computed Settings Management ===

    public function markAsComputed(
        PayoutProfile $inheritedProfile,
        int $sourceAgentId,
        CommissionSharingSettings $inheritedSettings
    ): self {
        return new self(
            agentId: $this->agentId,
            payoutProfile: $this->payoutProfile,
            payoutProfileSourceAgentId: $this->payoutProfileSourceAgentId,
            hasCustomPayoutProfile: $this->hasCustomPayoutProfile,
            commissionSharingSettings: $this->commissionSharingSettings,
            effectivePayoutProfile: $inheritedProfile,
            effectivePayoutSourceAgentId: $sourceAgentId,
            effectiveCommissionSharingSettings: $inheritedSettings,
            isComputed: true,
            computedAt: Carbon::now(),
            cacheExpiresAt: Carbon::now()->addHours(24),
            bettingLimits: $this->bettingLimits,
            blockedNumbers: $this->blockedNumbers,
            autoSettlement: $this->autoSettlement,
            isActive: $this->isActive,
            createdAt: $this->createdAt,
            updatedAt: Carbon::now()
        );
    }

    public function refreshCache(): self
    {
        return new self(
            agentId: $this->agentId,
            payoutProfile: $this->payoutProfile,
            payoutProfileSourceAgentId: $this->payoutProfileSourceAgentId,
            hasCustomPayoutProfile: $this->hasCustomPayoutProfile,
            commissionSharingSettings: $this->commissionSharingSettings,
            effectivePayoutProfile: $this->effectivePayoutProfile,
            effectivePayoutSourceAgentId: $this->effectivePayoutSourceAgentId,
            effectiveCommissionSharingSettings: $this->effectiveCommissionSharingSettings,
            isComputed: $this->isComputed,
            computedAt: Carbon::now(),
            cacheExpiresAt: Carbon::now()->addHours(24),
            bettingLimits: $this->bettingLimits,
            blockedNumbers: $this->blockedNumbers,
            autoSettlement: $this->autoSettlement,
            isActive: $this->isActive,
            createdAt: $this->createdAt,
            updatedAt: Carbon::now()
        );
    }

    // === Serialization ===

    public function toArray(): array
    {
        return [
            'agent_id' => $this->agentId,
            'payout_profile' => $this->payoutProfile?->toArray(),
            'payout_profile_source_agent_id' => $this->payoutProfileSourceAgentId,
            'has_custom_payout_profile' => $this->hasCustomPayoutProfile,
            'commission_sharing_settings' => $this->commissionSharingSettings->toArray(),
            'effective_payout_profile' => $this->effectivePayoutProfile->toArray(),
            'effective_payout_source_agent_id' => $this->effectivePayoutSourceAgentId,
            'effective_commission_sharing_settings' => $this->effectiveCommissionSharingSettings->toArray(),
            'is_computed' => $this->isComputed,
            'computed_at' => $this->computedAt?->toISOString(),
            'cache_expires_at' => $this->cacheExpiresAt?->toISOString(),
            'betting_limits' => $this->bettingLimits,
            'blocked_numbers' => $this->blockedNumbers,
            'auto_settlement' => $this->autoSettlement,
            'is_active' => $this->isActive,
        ];
    }
}

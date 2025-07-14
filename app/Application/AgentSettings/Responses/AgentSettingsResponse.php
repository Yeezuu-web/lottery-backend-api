<?php

declare(strict_types=1);

namespace App\Application\AgentSettings\Responses;

use App\Domain\AgentSettings\Models\AgentSettings;
use JsonSerializable;

final readonly class AgentSettingsResponse implements JsonSerializable
{
    public function __construct(
        public int $agentId,
        public ?array $payoutProfile,
        public ?int $payoutProfileSourceAgentId,
        public bool $hasCustomPayoutProfile,
        public array $commissionSharingSettings,
        public array $effectivePayoutProfile,
        public int $effectivePayoutSourceAgentId,
        public array $effectiveCommissionSharingSettings,
        public bool $isComputed,
        public ?string $computedAt,
        public ?string $cacheExpiresAt,
        public array $bettingLimits,
        public array $blockedNumbers,
        public bool $autoSettlement,
        public bool $isActive
    ) {}

    public static function fromDomain(AgentSettings $agentSettings): self
    {
        $data = $agentSettings->toArray();

        return new self(
            agentId: $data['agent_id'],
            payoutProfile: $data['payout_profile'],
            payoutProfileSourceAgentId: $data['payout_profile_source_agent_id'],
            hasCustomPayoutProfile: $data['has_custom_payout_profile'],
            commissionSharingSettings: $data['commission_sharing_settings'],
            effectivePayoutProfile: $data['effective_payout_profile'],
            effectivePayoutSourceAgentId: $data['effective_payout_source_agent_id'],
            effectiveCommissionSharingSettings: $data['effective_commission_sharing_settings'],
            isComputed: $data['is_computed'],
            computedAt: $data['computed_at'],
            cacheExpiresAt: $data['cache_expires_at'],
            bettingLimits: $data['betting_limits'],
            blockedNumbers: $data['blocked_numbers'],
            autoSettlement: $data['auto_settlement'],
            isActive: $data['is_active']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'agent_id' => $this->agentId,
            'payout_profile' => $this->payoutProfile,
            'payout_profile_source_agent_id' => $this->payoutProfileSourceAgentId,
            'has_custom_payout_profile' => $this->hasCustomPayoutProfile,
            'commission_sharing_settings' => $this->commissionSharingSettings,
            'effective_payout_profile' => $this->effectivePayoutProfile,
            'effective_payout_source_agent_id' => $this->effectivePayoutSourceAgentId,
            'effective_commission_sharing_settings' => $this->effectiveCommissionSharingSettings,
            'is_computed' => $this->isComputed,
            'computed_at' => $this->computedAt,
            'cache_expires_at' => $this->cacheExpiresAt,
            'betting_limits' => $this->bettingLimits,
            'blocked_numbers' => $this->blockedNumbers,
            'auto_settlement' => $this->autoSettlement,
            'is_active' => $this->isActive,
        ];
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}

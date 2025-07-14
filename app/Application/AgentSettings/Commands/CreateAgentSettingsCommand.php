<?php

namespace App\Application\AgentSettings\Commands;

final class CreateAgentSettingsCommand
{
    public function __construct(
        public readonly int $agentId,
        public readonly ?array $payoutProfile = null,
        public readonly ?float $commissionRate = null,
        public readonly ?float $sharingRate = null,
        public readonly array $bettingLimits = [],
        public readonly array $blockedNumbers = [],
        public readonly bool $autoSettlement = false,
        public readonly bool $isActive = true
    ) {}

    public function toArray(): array
    {
        return [
            'agent_id' => $this->agentId,
            'payout_profile' => $this->payoutProfile,
            'commission_rate' => $this->commissionRate,
            'sharing_rate' => $this->sharingRate,
            'betting_limits' => $this->bettingLimits,
            'blocked_numbers' => $this->blockedNumbers,
            'auto_settlement' => $this->autoSettlement,
            'is_active' => $this->isActive,
        ];
    }
}

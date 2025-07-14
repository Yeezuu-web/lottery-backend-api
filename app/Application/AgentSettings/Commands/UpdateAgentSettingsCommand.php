<?php

declare(strict_types=1);

namespace App\Application\AgentSettings\Commands;

final readonly class UpdateAgentSettingsCommand
{
    public function __construct(
        public int $agentId,
        public ?array $payoutProfile = null,
        public ?float $commissionRate = null,
        public ?float $sharingRate = null,
        public ?array $bettingLimits = null,
        public ?array $blockedNumbers = null,
        public ?bool $autoSettlement = null,
        public ?bool $isActive = null
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

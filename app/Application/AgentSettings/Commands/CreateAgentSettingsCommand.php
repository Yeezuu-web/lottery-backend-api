<?php

declare(strict_types=1);

namespace App\Application\AgentSettings\Commands;

final readonly class CreateAgentSettingsCommand
{
    public function __construct(
        public int $agentId,
        public ?int $dailyLimit = null,
        public float $maxCommission = 0.0,
        public float $maxShare = 0.0,
        public array $numberLimits = [],
        public array $blockedNumbers = []
    ) {}

    public function toArray(): array
    {
        return [
            'agent_id' => $this->agentId,
            'daily_limit' => $this->dailyLimit,
            'max_commission' => $this->maxCommission,
            'max_share' => $this->maxShare,
            'number_limits' => $this->numberLimits,
            'blocked_numbers' => $this->blockedNumbers,
        ];
    }
}

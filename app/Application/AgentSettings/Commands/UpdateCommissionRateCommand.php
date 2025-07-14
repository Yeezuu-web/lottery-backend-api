<?php

namespace App\Application\AgentSettings\Commands;

final class UpdateCommissionRateCommand
{
    public function __construct(
        public readonly int $agentId,
        public readonly ?float $commissionRate
    ) {}

    public function toArray(): array
    {
        return [
            'agent_id' => $this->agentId,
            'commission_rate' => $this->commissionRate,
        ];
    }
}

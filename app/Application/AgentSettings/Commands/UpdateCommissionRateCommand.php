<?php

declare(strict_types=1);

namespace App\Application\AgentSettings\Commands;

final readonly class UpdateCommissionRateCommand
{
    public function __construct(
        public int $agentId,
        public ?float $commissionRate
    ) {}

    public function toArray(): array
    {
        return [
            'agent_id' => $this->agentId,
            'commission_rate' => $this->commissionRate,
        ];
    }
}

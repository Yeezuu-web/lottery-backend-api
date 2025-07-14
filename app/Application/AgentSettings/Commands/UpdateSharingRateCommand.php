<?php

namespace App\Application\AgentSettings\Commands;

final class UpdateSharingRateCommand
{
    public function __construct(
        public readonly int $agentId,
        public readonly ?float $sharingRate
    ) {}

    public function toArray(): array
    {
        return [
            'agent_id' => $this->agentId,
            'sharing_rate' => $this->sharingRate,
        ];
    }
}

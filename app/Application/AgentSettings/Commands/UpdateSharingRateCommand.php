<?php

declare(strict_types=1);

namespace App\Application\AgentSettings\Commands;

final readonly class UpdateSharingRateCommand
{
    public function __construct(
        public int $agentId,
        public ?float $sharingRate
    ) {}

    public function toArray(): array
    {
        return [
            'agent_id' => $this->agentId,
            'sharing_rate' => $this->sharingRate,
        ];
    }
}

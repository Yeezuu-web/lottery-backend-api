<?php

declare(strict_types=1);

namespace App\Application\AgentSettings\Commands;

final readonly class UpdateAgentSettingsCommand
{
    public function __construct(
        public int $agentId,
        public ?int $dailyLimit = null,
        public ?float $maxCommission = null,
        public ?float $maxShare = null,
        public ?array $numberLimits = null,
        public ?array $blockedNumbers = null
    ) {}

    public function toArray(): array
    {
        $data = ['agent_id' => $this->agentId];

        if ($this->dailyLimit !== null) {
            $data['daily_limit'] = $this->dailyLimit;
        }

        if ($this->maxCommission !== null) {
            $data['max_commission'] = $this->maxCommission;
        }

        if ($this->maxShare !== null) {
            $data['max_share'] = $this->maxShare;
        }

        if ($this->numberLimits !== null) {
            $data['number_limits'] = $this->numberLimits;
        }

        if ($this->blockedNumbers !== null) {
            $data['blocked_numbers'] = $this->blockedNumbers;
        }

        return $data;
    }
}

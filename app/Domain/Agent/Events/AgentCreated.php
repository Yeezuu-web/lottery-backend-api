<?php

declare(strict_types=1);

namespace App\Domain\Agent\Events;

use App\Domain\Agent\Models\Agent;
use Carbon\Carbon;

final readonly class AgentCreated
{
    public function __construct(
        public Agent $agent,
        public Carbon $occurredAt
    ) {}

    public static function now(Agent $agent): self
    {
        return new self(
            agent: $agent,
            occurredAt: Carbon::now()
        );
    }

    public function toArray(): array
    {
        return [
            'agent_id' => $this->agent->id(),
            'username' => $this->agent->username()->value(),
            'agent_type' => $this->agent->agentType()->value(),
            'upline_id' => $this->agent->uplineId(),
            'occurred_at' => $this->occurredAt->toISOString(),
        ];
    }
}

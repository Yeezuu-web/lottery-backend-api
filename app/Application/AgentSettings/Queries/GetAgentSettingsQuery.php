<?php

declare(strict_types=1);

namespace App\Application\AgentSettings\Queries;

final readonly class GetAgentSettingsQuery
{
    public function __construct(
        public int $agentId,
        public bool $includeUsage = true
    ) {}

    public function toArray(): array
    {
        return [
            'agent_id' => $this->agentId,
            'include_usage' => $this->includeUsage,
        ];
    }
}

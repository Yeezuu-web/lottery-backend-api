<?php

declare(strict_types=1);

namespace App\Application\AgentSettings\Queries;

final readonly class GetAgentSettingsQuery
{
    public function __construct(
        public int $agentId,
        public bool $includeEffectiveSettings = true,
        public bool $refreshCache = false
    ) {}

    public function toArray(): array
    {
        return [
            'agent_id' => $this->agentId,
            'include_effective_settings' => $this->includeEffectiveSettings,
            'refresh_cache' => $this->refreshCache,
        ];
    }
}

<?php

namespace App\Application\AgentSettings\Queries;

final class GetAgentSettingsQuery
{
    public function __construct(
        public readonly int $agentId,
        public readonly bool $includeEffectiveSettings = true,
        public readonly bool $refreshCache = false
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

<?php

namespace App\Application\AgentSettings\Contracts;

use App\Domain\AgentSettings\Models\AgentSettings;

interface AgentSettingsRepositoryInterface
{
    public function findByAgentId(int $agentId): ?AgentSettings;

    public function save(AgentSettings $agentSettings): AgentSettings;

    public function delete(int $agentId): bool;

    public function exists(int $agentId): bool;

    public function findByAgentIds(array $agentIds): array;

    public function findWithExpiredCache(): array;

    public function refreshCache(int $agentId): ?AgentSettings;

    public function getAllActive(): array;

    public function getInheritanceChain(int $agentId): array;
}

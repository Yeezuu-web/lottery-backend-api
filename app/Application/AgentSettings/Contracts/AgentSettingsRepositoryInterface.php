<?php

declare(strict_types=1);

namespace App\Application\AgentSettings\Contracts;

use App\Domain\AgentSettings\Models\AgentSettings;

interface AgentSettingsRepositoryInterface
{
    /**
     * Find agent settings by agent ID
     */
    public function findByAgentId(int $agentId): ?AgentSettings;

    /**
     * Save agent settings
     */
    public function save(AgentSettings $agentSettings): AgentSettings;

    /**
     * Delete agent settings
     */
    public function delete(int $agentId): bool;

    /**
     * Get daily usage for agent (current date)
     */
    public function getDailyUsage(int $agentId): int;

    /**
     * Get number usage for agent (current date)
     */
    public function getNumberUsage(int $agentId): array;

    /**
     * Increment daily usage (for caching)
     */
    public function incrementDailyUsage(int $agentId, int $amount): void;

    /**
     * Increment number usage (for caching)
     */
    public function incrementNumberUsage(int $agentId, string $gameType, string $number, int $amount): void;

    /**
     * Get active agent settings
     */
    public function getActiveSettings(): array;

    /**
     * Check if agent has settings
     */
    public function hasSettings(int $agentId): bool;
}

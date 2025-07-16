<?php

declare(strict_types=1);

namespace App\Domain\Agent\Contracts;

use App\Domain\Agent\Models\Agent;
use App\Domain\Agent\ValueObjects\AgentType;
use App\Domain\Agent\ValueObjects\Username;

interface AgentRepositoryInterface
{
    /**
     * Find agent by ID
     */
    public function findById(int $id): ?Agent;

    /**
     * Find agent by username
     */
    public function findByUsername(Username $username): ?Agent;

    /**
     * Find agent by email
     */
    public function findByEmail(string $email): ?Agent;

    /**
     * Get all direct downlines of an agent
     */
    public function getDirectDownlines(int $agentId): array;

    /**
     * Get all agents in hierarchy path (all downlines, not just direct)
     */
    public function getHierarchyDownlines(int $agentId): array;

    /**
     * Get direct downlines with wallet data
     */
    public function getDirectDownlinesWithWallets(int $agentId): array;

    /**
     * Get hierarchy downlines with wallet data
     */
    public function getHierarchyDownlinesWithWallets(int $agentId): array;

    /**
     * Get wallet data for an agent
     */
    public function getAgentWallets(int $agentId): array;

    /**
     * Get agents by upline ID
     */
    public function getByUplineId(int $uplineId): array;

    /**
     * Get agents by agent type
     */
    public function getByAgentType(AgentType $agentType): array;

    /**
     * Check if username exists
     */
    public function usernameExists(Username $username): bool;

    /**
     * Check if email exists
     */
    public function emailExists(string $email): bool;

    /**
     * Get next available username for given agent type and parent
     */
    public function getNextAvailableUsername(AgentType $agentType, ?Username $parentUsername = null): Username;

    /**
     * Get agents that can be managed by given agent
     */
    public function getManagedAgents(int $agentId): array;

    /**
     * Save agent (create or update)
     */
    public function save(Agent $agent): Agent;

    /**
     * Delete agent
     */
    public function delete(int $id): bool;

    /**
     * Get agent count by type
     */
    public function getCountByType(AgentType $agentType): int;

    /**
     * Get active agents count
     */
    public function getActiveAgentsCount(): int;

    /**
     * Search agents by criteria
     */
    public function search(array $criteria): array;

    /**
     * Get agents with pagination
     */
    public function paginate(int $page, int $perPage, array $criteria = []): array;

    /**
     * Verify agent password (for authentication)
     */
    public function verifyPassword(Agent $agent, string $password): bool;
}

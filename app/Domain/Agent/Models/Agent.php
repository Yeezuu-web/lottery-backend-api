<?php

declare(strict_types=1);

namespace App\Domain\Agent\Models;

use App\Domain\Agent\ValueObjects\AgentType;
use App\Domain\Agent\ValueObjects\Username;
use App\Shared\Exceptions\ValidationException;
use DateTimeImmutable;

final readonly class Agent
{
    private Username $username;

    private AgentType $agentType;

    private ?int $uplineId;

    public function __construct(
        private int $id,
        Username $username,
        AgentType $agentType,
        ?int $uplineId,
        private string $name,
        private string $email,
        private ?string $status = null,
        private bool $isActive = true,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $updatedAt = null,
        private ?string $password = null
    ) {
        $this->validateAgentData($username, $agentType, $uplineId);
        $this->username = $username;
        $this->agentType = $agentType;
        $this->uplineId = $uplineId;
    }

    /**
     * Create a new agent instance
     */
    public static function create(
        int $id,
        string $username,
        string $agentType,
        ?int $uplineId,
        string $name,
        string $email,
        ?string $status = null,
        bool $isActive = true,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?string $password = null
    ): self {
        return new self(
            $id,
            new Username($username),
            new AgentType($agentType),
            $uplineId,
            $name,
            $email,
            $status,
            $isActive,
            $createdAt,
            $updatedAt,
            $password
        );
    }

    public static function update(
        int $id,
        string $username,
        string $agentType,
        ?int $uplineId,
        ?string $name,
        ?string $email,
        ?string $password,
        ?string $status = null,
        ?bool $isActive = true,
        ?string $createdAt = null,
        ?string $updatedAt = null,
    ): self {
        $createdAt = $createdAt !== null && $createdAt !== '' && $createdAt !== '0' ? new DateTimeImmutable($createdAt) : null;
        $updatedAt = $updatedAt !== null && $updatedAt !== '' && $updatedAt !== '0' ? new DateTimeImmutable($updatedAt) : null;

        return new self(
            $id,
            new Username($username),
            new AgentType($agentType),
            $uplineId,
            $name,
            $email,
            $status,
            $isActive,
            $createdAt,
            $updatedAt,
            $password,
        );
    }

    public function id(): int
    {
        return $this->id;
    }

    public function username(): Username
    {
        return $this->username;
    }

    public function agentType(): AgentType
    {
        return $this->agentType;
    }

    public function uplineId(): ?int
    {
        return $this->uplineId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function status(): ?string
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function password(): ?string
    {
        return $this->password;
    }

    /**
     * Check if this agent can manage another agent
     */
    public function canManage(self $otherAgent): bool
    {
        // Must be higher in hierarchy
        if (! $this->agentType->canManage($otherAgent->agentType())) {
            return false;
        }

        // Must be in the same hierarchy path
        return $this->isInHierarchyPath($otherAgent);
    }

    /**
     * Check if this agent is in the hierarchy path of another agent
     */
    public function isInHierarchyPath(self $otherAgent): bool
    {
        // Check if other agent's username starts with this agent's username
        return str_starts_with($otherAgent->username()->value(), $this->username->value());
    }

    /**
     * Check if this agent is a direct child of another agent
     */
    public function isDirectChildOf(self $parentAgent): bool
    {
        // Check by upline ID
        if ($this->uplineId !== $parentAgent->id()) {
            return false;
        }

        // Check by username hierarchy
        return $this->username->isChildOf($parentAgent->username());
    }

    /**
     * Check if this agent can create an agent of given type
     */
    public function canCreateAgentType(AgentType $targetType): bool
    {
        // Must be able to manage sub-agents
        if (! $this->agentType->canManageSubAgents()) {
            return false;
        }

        // Must be higher in hierarchy
        return $this->agentType->canManage($targetType);
    }

    /**
     * Get expected username prefix for creating sub-agents
     */
    public function getSubAgentUsernamePrefix(): string
    {
        return $this->username->value();
    }

    /**
     * Check if agent uses upline dashboard
     */
    public function usesUplineDashboard(): bool
    {
        return $this->agentType->canAccessUpline();
    }

    /**
     * Check if agent uses member interface
     */
    public function usesMemberInterface(): bool
    {
        return $this->agentType->canAccessMember();
    }

    /**
     * Get agent's hierarchy level
     */
    public function getHierarchyLevel(): int
    {
        return $this->agentType->getHierarchyLevel();
    }

    /**
     * Check if agent is company level
     */
    public function isCompany(): bool
    {
        return $this->agentType->isCompany();
    }

    /**
     * Check if agent is member level
     */
    public function isMember(): bool
    {
        return $this->agentType->isMember();
    }

    /**
     * Check if agent can place bets
     */
    public function canPlaceBets(): bool
    {
        return $this->isActive && $this->agentType->canPlaceBets();
    }

    /**
     * Get display information for UI
     */
    public function getDisplayInfo(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username->value(),
            'name' => $this->name,
            'email' => $this->email,
            'agent_type' => $this->agentType->value(),
            'agent_type_display' => $this->agentType->getDisplayName(),
            'upline_id' => $this->uplineId,
            'is_active' => $this->isActive,
            'hierarchy_level' => $this->getHierarchyLevel(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Validate agent data consistency
     */
    private function validateAgentData(Username $username, AgentType $agentType, ?int $uplineId): void
    {
        // Validate username matches agent type
        if (! $username->isValidForAgentType($agentType)) {
            throw new ValidationException(
                sprintf("Username '%s' is not valid for agent type '%s'", $username->value(), $agentType->value())
            );
        }

        // Validate upline requirements
        if ($agentType->isCompany() && $uplineId !== null) {
            throw new ValidationException('Company agent cannot have upline');
        }

        if (! $agentType->isCompany() && $uplineId === null) {
            throw new ValidationException('Non-company agent must have upline');
        }
    }
}

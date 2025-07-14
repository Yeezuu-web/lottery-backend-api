<?php

namespace App\Application\Agent\Responses;

use App\Domain\Agent\Models\Agent;

final class AgentResponse
{
    public function __construct(
        private readonly int $id,
        private readonly string $username,
        private readonly string $name,
        private readonly string $email,
        private readonly string $agentType,
        private readonly string $agentTypeDisplay,
        private readonly ?int $uplineId,
        private readonly bool $isActive,
        private readonly int $hierarchyLevel,
        private readonly string $createdAt,
        private readonly ?string $updatedAt,
        private readonly array $permissions = [],
        private readonly array $statistics = []
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getAgentType(): string
    {
        return $this->agentType;
    }

    public function getAgentTypeDisplay(): string
    {
        return $this->agentTypeDisplay;
    }

    public function getUplineId(): ?int
    {
        return $this->uplineId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getHierarchyLevel(): int
    {
        return $this->hierarchyLevel;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getStatistics(): array
    {
        return $this->statistics;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'name' => $this->name,
            'email' => $this->email,
            'agent_type' => $this->agentType,
            'agent_type_display' => $this->agentTypeDisplay,
            'upline_id' => $this->uplineId,
            'is_active' => $this->isActive,
            'hierarchy_level' => $this->hierarchyLevel,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'permissions' => $this->permissions,
            'statistics' => $this->statistics,
        ];
    }

    public static function fromDomain(Agent $agent, array $permissions = [], array $statistics = []): self
    {
        return new self(
            $agent->id(),
            $agent->username()->value(),
            $agent->name(),
            $agent->email(),
            $agent->agentType()->value(),
            $agent->agentType()->getDisplayName(),
            $agent->uplineId(),
            $agent->isActive(),
            $agent->getHierarchyLevel(),
            $agent->createdAt()->format('Y-m-d H:i:s'),
            $agent->updatedAt()?->format('Y-m-d H:i:s'),
            $permissions,
            $statistics
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Agent\Responses;

use App\Domain\Agent\Models\Agent;

final readonly class AgentResponse
{
    public function __construct(
        private int $id,
        private string $username,
        private string $name,
        private string $email,
        private string $agentType,
        private string $agentTypeDisplay,
        private ?int $uplineId,
        private ?string $status,
        private bool $isActive,
        private int $hierarchyLevel,
        private string $createdAt,
        private ?string $updatedAt,
        private array $permissions = [],
        private array $statistics = [],
        private array $wallets = []
    ) {}

    public static function fromDomain(Agent $agent, array $permissions = [], array $statistics = [], array $wallets = []): self
    {
        return new self(
            $agent->id(),
            $agent->username()->value(),
            $agent->name(),
            $agent->email(),
            $agent->agentType()->value(),
            $agent->agentType()->getDisplayName(),
            $agent->uplineId(),
            $agent->status(),
            $agent->isActive(),
            $agent->getHierarchyLevel(),
            $agent->createdAt()->format('Y-m-d H:i:s'),
            $agent->updatedAt()?->format('Y-m-d H:i:s'),
            $permissions,
            $statistics,
            $wallets
        );
    }

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

    public function getStatus(): ?string
    {
        return $this->status;
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

    public function getWallets(): array
    {
        return $this->wallets;
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
            'status' => $this->status,
            'is_active' => $this->isActive,
            'hierarchy_level' => $this->hierarchyLevel,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'permissions' => $this->permissions,
            'statistics' => $this->statistics,
            'wallets' => $this->wallets,
        ];
    }
}

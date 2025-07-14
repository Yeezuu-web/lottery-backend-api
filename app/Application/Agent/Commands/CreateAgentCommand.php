<?php

declare(strict_types=1);

namespace App\Application\Agent\Commands;

final readonly class CreateAgentCommand
{
    public function __construct(
        private string $username,
        private string $agentType,
        private int $creatorId,
        private string $name,
        private string $email,
        private ?string $password = null,
        private ?int $uplineId = null
    ) {}

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getAgentType(): string
    {
        return $this->agentType;
    }

    public function getCreatorId(): int
    {
        return $this->creatorId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getUplineId(): ?int
    {
        return $this->uplineId;
    }

    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'agent_type' => $this->agentType,
            'creator_id' => $this->creatorId,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'upline_id' => $this->uplineId,
        ];
    }
}

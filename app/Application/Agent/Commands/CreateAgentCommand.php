<?php

namespace App\Application\Agent\Commands;

final class CreateAgentCommand
{
    public function __construct(
        private readonly string $username,
        private readonly string $agentType,
        private readonly int $creatorId,
        private readonly string $name,
        private readonly string $email,
        private readonly ?string $password = null,
        private readonly ?int $uplineId = null
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

<?php

declare(strict_types=1);

namespace App\Application\Agent\Commands;

final class UpdateAgentCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $email,
        public ?string $password,
        public int $updatorId,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getUpdatorId(): int
    {
        return $this->updatorId;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'updator_id' => $this->updatorId,
        ];
    }
}

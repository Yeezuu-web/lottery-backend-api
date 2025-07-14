<?php

declare(strict_types=1);

namespace App\Domain\Auth\ValueObjects;

use App\Domain\Agent\Models\Agent;
use DateTimeImmutable;

final readonly class AuthResult
{
    private DateTimeImmutable $authenticatedAt;

    private function __construct(
        private bool $success,
        private ?Agent $agent = null,
        private ?TokenPair $tokenPair = null,
        private ?string $errorMessage = null
    ) {
        $this->authenticatedAt = new DateTimeImmutable;
    }

    public static function success(Agent $agent, TokenPair $tokenPair): self
    {
        return new self(true, $agent, $tokenPair);
    }

    public static function failure(string $errorMessage): self
    {
        return new self(false, null, null, $errorMessage);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return ! $this->success;
    }

    public function agent(): ?Agent
    {
        return $this->agent;
    }

    public function tokenPair(): ?TokenPair
    {
        return $this->tokenPair;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function authenticatedAt(): DateTimeImmutable
    {
        return $this->authenticatedAt;
    }

    public function toArray(): array
    {
        if ($this->isFailure()) {
            return [
                'success' => false,
                'message' => $this->errorMessage,
                'authenticated_at' => $this->authenticatedAt->format('c'),
            ];
        }

        return [
            'success' => true,
            'agent' => [
                'id' => $this->agent->id(),
                'username' => $this->agent->username()->value(),
                'email' => $this->agent->email(),
                'name' => $this->agent->name(),
                'agent_type' => $this->agent->agentType()->value(),
                'is_active' => $this->agent->isActive(),
            ],
            'tokens' => $this->tokenPair->toArray(),
            'authenticated_at' => $this->authenticatedAt->format('c'),
        ];
    }
}

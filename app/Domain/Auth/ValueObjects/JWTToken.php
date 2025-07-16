<?php

declare(strict_types=1);

namespace App\Domain\Auth\ValueObjects;

use DateTimeImmutable;
use Stringable;

final readonly class JWTToken implements Stringable
{
    public function __construct(private string $token, private array $payload, private DateTimeImmutable $expiresAt) {}

    public function __toString(): string
    {
        return $this->token;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        return new DateTimeImmutable > $this->expiresAt;
    }

    public function audience(): string
    {
        return $this->payload['aud'] ?? '';
    }

    public function getAgentId(): int
    {
        return $this->payload['agent_id'] ?? 0;
    }

    public function getUsername(): string
    {
        return $this->payload['username'] ?? '';
    }

    public function getAgentType(): string
    {
        return $this->payload['agent_type'] ?? '';
    }

    public function getPermissions(): array
    {
        return $this->payload['permissions'] ?? [];
    }

    public function getJti(): string
    {
        return $this->payload['jti'] ?? '';
    }

    public function getType(): string
    {
        return $this->payload['type'] ?? '';
    }

    public function isAccessToken(): bool
    {
        return $this->getType() === 'access';
    }

    public function isRefreshToken(): bool
    {
        return $this->getType() === 'refresh';
    }

    public function id(): ?string
    {
        return $this->payload['id'];
    }

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'payload' => $this->payload,
            'expires_at' => $this->expiresAt->format('c'),
            'is_expired' => $this->isExpired(),
        ];
    }
}

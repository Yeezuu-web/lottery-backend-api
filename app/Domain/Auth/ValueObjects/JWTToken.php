<?php

namespace App\Domain\Auth\ValueObjects;

use DateTime;

final class JWTToken
{
    private readonly string $token;

    private readonly array $payload;

    private readonly DateTime $expiresAt;

    public function __construct(string $token, array $payload, DateTime $expiresAt)
    {
        $this->token = $token;
        $this->payload = $payload;
        $this->expiresAt = $expiresAt;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function expiresAt(): DateTime
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        return new DateTime > $this->expiresAt;
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

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'payload' => $this->payload,
            'expires_at' => $this->expiresAt->format('c'),
            'is_expired' => $this->isExpired(),
        ];
    }

    public function __toString(): string
    {
        return $this->token;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Auth\ValueObjects;

use App\Shared\Exceptions\ValidationException;
use DateTimeImmutable;

final readonly class TokenPair
{
    private JWTToken $accessToken;

    private JWTToken $refreshToken;

    public function __construct(JWTToken $accessToken, JWTToken $refreshToken)
    {
        $this->validateTokenPair($accessToken, $refreshToken);

        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
    }

    public function accessToken(): JWTToken
    {
        return $this->accessToken;
    }

    public function refreshToken(): JWTToken
    {
        return $this->refreshToken;
    }

    public function isValid(): bool
    {
        return ! $this->accessToken->isExpired();
    }

    public function needsRefresh(): bool
    {
        // Refresh if access token expires within 5 minutes
        $threshold = new DateTimeImmutable('+5 minutes');

        return $this->accessToken->expiresAt() <= $threshold;
    }

    public function canBeRefreshed(): bool
    {
        return ! $this->refreshToken->isExpired();
    }

    public function audience(): string
    {
        return $this->accessToken->audience();
    }

    public function getAgentId(): int
    {
        return $this->accessToken->getAgentId();
    }

    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken->token(),
            'refresh_token' => $this->refreshToken->token(),
            'token_type' => 'Bearer',
            'expires_at' => $this->accessToken->expiresAt()->format('c'),
            'audience' => $this->accessToken->audience(),
        ];
    }

    private function validateTokenPair(JWTToken $accessToken, JWTToken $refreshToken): void
    {
        // Both tokens must have the same audience
        if ($accessToken->audience() !== $refreshToken->audience()) {
            throw new ValidationException('Access and refresh tokens must have the same audience');
        }

        // Both tokens must be for the same agent
        if ($accessToken->getAgentId() !== $refreshToken->getAgentId()) {
            throw new ValidationException('Access and refresh tokens must be for the same agent');
        }

        // Refresh token should expire after access token
        if ($refreshToken->expiresAt() <= $accessToken->expiresAt()) {
            throw new ValidationException('Refresh token must expire after access token');
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth\Contracts;

use App\Domain\Auth\ValueObjects\JWTToken;

interface AuthenticationServiceInterface
{
    /**
     * Store refresh token information
     */
    public function storeRefreshToken(JWTToken $refreshToken): void;

    /**
     * Blacklist a token
     */
    public function blacklistToken(JWTToken $token): bool;

    /**
     * Check if token is blacklisted
     */
    public function isTokenBlacklisted(JWTToken $token): bool;

    /**
     * Verify token and return decoded token
     */
    public function verifyToken(string $token, string $audience): ?JWTToken;

    /**
     * Find refresh token by agent ID and audience
     */
    public function findRefreshTokenByAgent(int $agentId, string $audience): ?JWTToken;

    /**
     * Clear session data for agent
     */
    public function clearSessionData(int $agentId): void;
}

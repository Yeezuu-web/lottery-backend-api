<?php

namespace App\Domain\Auth\Contracts;

use App\Domain\Agent\Models\Agent;
use App\Domain\Auth\ValueObjects\AuthResult;
use App\Domain\Auth\ValueObjects\JWTToken;

interface AuthenticationServiceInterface
{
    /**
     * Authenticate agent with username and password
     */
    public function authenticate(string $username, string $password, string $audience): AuthResult;

    /**
     * Refresh tokens using refresh token
     */
    public function refreshTokens(JWTToken $refreshToken): AuthResult;

    /**
     * Verify and decode JWT token
     */
    public function verifyToken(string $token, string $audience): ?JWTToken;

    /**
     * Invalidate token (logout)
     */
    public function invalidateToken(JWTToken $token): bool;

    /**
     * Check if agent can authenticate for specific audience
     */
    public function canAuthenticateForAudience(Agent $agent, string $audience): bool;
}

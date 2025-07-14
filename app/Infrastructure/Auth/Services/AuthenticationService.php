<?php

namespace App\Infrastructure\Auth\Services;

use App\Domain\Auth\Contracts\TokenServiceInterface;
use App\Domain\Auth\ValueObjects\JWTToken;
use App\Infrastructure\Auth\Contracts\AuthenticationServiceInterface;
use Exception;
use Illuminate\Support\Facades\Cache;

/**
 * Infrastructure service for authentication-related operations
 * Handles token blacklisting, session management, and other infrastructure concerns
 */
final class AuthenticationService implements AuthenticationServiceInterface
{
    private readonly TokenServiceInterface $tokenService;

    public function __construct(TokenServiceInterface $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Store refresh token in cache for tracking
     */
    public function storeRefreshToken(JWTToken $refreshToken): void
    {
        if (! config('jwt.blacklist.enabled')) {
            return;
        }

        $key = $this->getTokenCacheKey($refreshToken);
        $ttl = $this->tokenService->getTokenTTL($refreshToken);

        Cache::put($key, [
            'agent_id' => $refreshToken->getAgentId(),
            'audience' => $refreshToken->audience(),
            'created_at' => now()->toDateTimeString(),
        ], $ttl);
    }

    /**
     * Blacklist a token
     */
    public function blacklistToken(JWTToken $token): bool
    {
        if (! config('jwt.blacklist.enabled')) {
            return true;
        }

        $key = $this->getBlacklistKey($token);
        $ttl = $this->tokenService->getTokenTTL($token);

        return Cache::put($key, [
            'blacklisted_at' => now()->toDateTimeString(),
            'agent_id' => $token->getAgentId(),
            'audience' => $token->audience(),
        ], $ttl);
    }

    /**
     * Check if token is blacklisted
     */
    public function isTokenBlacklisted(JWTToken $token): bool
    {
        if (! config('jwt.blacklist.enabled')) {
            return false;
        }

        $key = $this->getBlacklistKey($token);

        return Cache::has($key);
    }

    /**
     * Verify token and check if it's blacklisted
     */
    public function verifyToken(string $token, string $audience): ?JWTToken
    {
        try {
            $jwtToken = $this->tokenService->decodeToken($token, $audience);

            if (! $jwtToken) {
                return null;
            }

            // Check if token is blacklisted
            if ($this->isTokenBlacklisted($jwtToken)) {
                return null;
            }

            return $jwtToken;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Find refresh token by agent and audience
     */
    public function findRefreshTokenByAgent(int $agentId, string $audience): ?JWTToken
    {
        // In a real implementation, you might store token mapping in database
        // For now, we'll return null as we don't have a direct way to retrieve
        // specific tokens from cache without knowing the token hash
        return null;
    }

    /**
     * Clear any session data for agent
     */
    public function clearSessionData(int $agentId): void
    {
        // Clear any session-specific data for the agent
        // This could include clearing specific cache entries, etc.
        $sessionKey = "session:agent:{$agentId}";
        Cache::forget($sessionKey);
    }

    /**
     * Get cache key for token
     */
    private function getTokenCacheKey(JWTToken $token): string
    {
        return sprintf(
            'jwt:token:%s:%s',
            $token->audience(),
            hash('sha256', $token->token())
        );
    }

    /**
     * Get blacklist cache key for token
     */
    private function getBlacklistKey(JWTToken $token): string
    {
        return sprintf(
            'jwt:blacklist:%s:%s',
            $token->audience(),
            hash('sha256', $token->token())
        );
    }
}

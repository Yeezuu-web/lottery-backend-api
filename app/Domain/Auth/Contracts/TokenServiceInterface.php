<?php

namespace App\Domain\Auth\Contracts;

use App\Domain\Agent\Models\Agent;
use App\Domain\Auth\ValueObjects\JWTToken;
use App\Domain\Auth\ValueObjects\TokenPair;

interface TokenServiceInterface
{
    /**
     * Generate access and refresh token pair
     */
    public function generateTokenPair(Agent $agent, string $audience): TokenPair;

    /**
     * Generate access token only
     */
    public function generateAccessToken(Agent $agent, string $audience): JWTToken;

    /**
     * Generate refresh token only
     */
    public function generateRefreshToken(Agent $agent, string $audience): JWTToken;

    /**
     * Decode and verify JWT token
     */
    public function decodeToken(string $token, string $audience): ?JWTToken;

    /**
     * Check if token is valid (not expired and properly signed)
     */
    public function isTokenValid(string $token, string $audience): bool;

    /**
     * Get remaining time for token expiration
     */
    public function getTokenTTL(JWTToken $token): int;
}

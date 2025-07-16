<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth\Services;

use App\Domain\Agent\Models\Agent;
use App\Domain\Auth\Contracts\TokenServiceInterface;
use App\Domain\Auth\Services\DatabaseAuthorizationService;
use App\Domain\Auth\ValueObjects\JWTToken;
use App\Domain\Auth\ValueObjects\TokenPair;
use DateTimeImmutable;
use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

final readonly class JWTTokenService implements TokenServiceInterface
{
    private array $jwtConfig;

    public function __construct(
        private DatabaseAuthorizationService $authorizationService
    ) {
        $this->jwtConfig = app('jwt.config');
    }

    public function generateTokenPair(Agent $agent, string $audience): TokenPair
    {
        $accessToken = $this->generateAccessToken($agent, $audience);
        $refreshToken = $this->generateRefreshToken($agent, $audience);

        return new TokenPair($accessToken, $refreshToken);
    }

    public function generateAccessToken(Agent $agent, string $audience): JWTToken
    {
        $config = $this->getAudienceConfig($audience);
        $now = new DateTimeImmutable;
        $expiresAt = (clone $now)->modify('+'.$config['access_token_ttl'].' seconds');

        $payload = [
            'iss' => $config['issuer'],
            'aud' => $config['audience'],
            'iat' => $now->getTimestamp(),
            'exp' => $expiresAt->getTimestamp(),
            'sub' => (string) $agent->id(),
            'jti' => uniqid('access_', true),
            'type' => 'access',

            // Agent data
            'agent_id' => $agent->id(),
            'username' => $agent->username(),
            'email' => $agent->email(),
            'agent_type' => $agent->agentType()->value(),
            'permissions' => $this->getAgentPermissions($agent),
        ];

        $token = JWT::encode($payload, $config['secret'], 'HS256');

        return new JWTToken($token, $payload, $expiresAt);
    }

    public function generateRefreshToken(Agent $agent, string $audience): JWTToken
    {
        $config = $this->getAudienceConfig($audience);
        $now = new DateTimeImmutable;
        $expiresAt = (clone $now)->modify('+'.$config['refresh_token_ttl'].' seconds');

        $payload = [
            'iss' => $config['issuer'],
            'aud' => $config['audience'],
            'iat' => $now->getTimestamp(),
            'exp' => $expiresAt->getTimestamp(),
            'sub' => (string) $agent->id(),
            'jti' => uniqid('refresh_', true),
            'type' => 'refresh',

            // Minimal agent data for refresh
            'agent_id' => $agent->id(),
            'username' => $agent->username(),
            'agent_type' => $agent->agentType()->value(),
        ];

        $token = JWT::encode($payload, $config['secret'], 'HS256');

        return new JWTToken($token, $payload, $expiresAt);
    }

    public function decodeToken(string $token, string $audience): ?JWTToken
    {
        try {
            $config = $this->getAudienceConfig($audience);

            $decoded = JWT::decode($token, new Key($config['secret'], 'HS256'));
            $payload = (array) $decoded;

            // Validate audience
            if ($payload['aud'] !== $audience) {
                return null;
            }

            $expiresAt = new DateTimeImmutable('@'.$payload['exp']);

            return new JWTToken($token, $payload, $expiresAt);

        } catch (ExpiredException|SignatureInvalidException|Exception) {
            return null;
        }
    }

    public function isTokenValid(string $token, string $audience): bool
    {
        return $this->decodeToken($token, $audience) instanceof JWTToken;
    }

    public function getTokenTTL(JWTToken $token): int
    {
        $now = new DateTimeImmutable;
        $expiresAt = $token->expiresAt();

        $diff = $expiresAt->getTimestamp() - $now->getTimestamp();

        return max(0, $diff);
    }

    public function isTokenExpired(JWTToken $token): bool
    {
        return $token->isExpired();
    }

    public function getTokenPayload(JWTToken $token): array
    {
        return $token->payload();
    }

    /**
     * Get configuration for specific audience
     */
    private function getAudienceConfig(string $audience): array
    {
        if (! isset($this->jwtConfig[$audience])) {
            throw new Exception('Invalid audience: '.$audience);
        }

        return $this->jwtConfig[$audience];
    }

    /**
     * Get agent permissions from database
     */
    private function getAgentPermissions(Agent $agent): array
    {
        return $this->authorizationService->getAgentPermissions($agent->id());
    }
}

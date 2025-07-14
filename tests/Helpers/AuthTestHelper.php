<?php

namespace Tests\Helpers;

use App\Application\Auth\DTOs\AuthenticateUserCommand;
use App\Application\Auth\DTOs\LogoutUserCommand;
use App\Application\Auth\DTOs\RefreshTokenCommand;
use App\Domain\Agent\Models\Agent;
use App\Domain\Agent\ValueObjects\AgentType;
use App\Domain\Agent\ValueObjects\Username;
use App\Domain\Auth\ValueObjects\JWTToken;
use App\Domain\Auth\ValueObjects\TokenPair;
use Carbon\Carbon;

class AuthTestHelper
{
    /**
     * Create a test agent
     */
    public static function createTestAgent(
        int $id = 123,
        string $username = 'A',
        string $email = 'test@example.com',
        string $name = 'Test User',
        string $agentType = 'company',
        bool $isActive = true
    ): Agent {
        // For non-company agents, provide a parent ID
        $uplineId = ($agentType === 'company') ? null : 1;

        return new Agent(
            $id,
            new Username($username),
            new AgentType($agentType),
            $uplineId,
            $name,
            $email,
            $isActive,
            Carbon::now()->toDateTimeImmutable(),
            Carbon::now()->toDateTimeImmutable()
        );
    }

    /**
     * Create a test JWT token
     */
    public static function createTestJWTToken(
        string $token = 'test-jwt-token',
        int $agentId = 123,
        string $audience = 'upline',
        ?Carbon $expiresAt = null
    ): JWTToken {
        $expiresAt = $expiresAt ?? Carbon::now()->addHour();

        $payload = [
            'iss' => 'lottery-api',
            'aud' => $audience,
            'agent_id' => $agentId,
            'username' => 'A',
            'email' => 'test@example.com',
            'agent_type' => 'company',
            'permissions' => ['read', 'write'],
            'iat' => Carbon::now()->timestamp,
            'exp' => $expiresAt->timestamp,
        ];

        return new JWTToken($token, $payload, $expiresAt->toDateTime());
    }

    /**
     * Create a test expired JWT token
     */
    public static function createExpiredJWTToken(
        string $token = 'expired-jwt-token',
        int $agentId = 123,
        string $audience = 'upline'
    ): JWTToken {
        return self::createTestJWTToken($token, $agentId, $audience, Carbon::now()->subHour());
    }

    /**
     * Create a test token pair
     */
    public static function createTestTokenPair(
        string $accessToken = 'test-access-token',
        string $refreshToken = 'test-refresh-token',
        int $agentId = 123,
        string $audience = 'upline'
    ): TokenPair {
        $accessTokenObj = self::createTestJWTToken($accessToken, $agentId, $audience);
        $refreshTokenObj = self::createTestJWTToken($refreshToken, $agentId, $audience, Carbon::now()->addWeek());

        return new TokenPair($accessTokenObj, $refreshTokenObj);
    }

    /**
     * Create an authenticate user command
     */
    public static function createAuthenticateUserCommand(
        string $username = 'A',
        string $password = 'password123',
        string $audience = 'upline'
    ): AuthenticateUserCommand {
        return new AuthenticateUserCommand($username, $password, $audience);
    }

    /**
     * Create a refresh token command
     */
    public static function createRefreshTokenCommand(
        string $refreshToken = 'test-refresh-token',
        string $audience = 'upline'
    ): RefreshTokenCommand {
        return new RefreshTokenCommand($refreshToken, $audience);
    }

    /**
     * Create a logout user command
     */
    public static function createLogoutUserCommand(
        ?JWTToken $token = null,
        string $audience = 'upline'
    ): LogoutUserCommand {
        $token = $token ?? self::createTestJWTToken('test-token', 123, $audience);

        return new LogoutUserCommand($token, $audience);
    }

    /**
     * Create mock agent repository
     */
    public static function createMockAgentRepository(): \PHPUnit\Framework\MockObject\MockObject
    {
        return \Mockery::mock(\App\Domain\Agent\Contracts\AgentRepositoryInterface::class);
    }

    /**
     * Create mock token service
     */
    public static function createMockTokenService(): \PHPUnit\Framework\MockObject\MockObject
    {
        return \Mockery::mock(\App\Domain\Auth\Contracts\TokenServiceInterface::class);
    }

    /**
     * Create mock authentication domain service
     */
    public static function createMockAuthDomainService(): \PHPUnit\Framework\MockObject\MockObject
    {
        return \Mockery::mock(\App\Domain\Auth\Contracts\AuthenticationDomainServiceInterface::class);
    }

    /**
     * Create mock authentication infrastructure service
     */
    public static function createMockAuthInfrastructureService(): \PHPUnit\Framework\MockObject\MockObject
    {
        return \Mockery::mock(\App\Infrastructure\Auth\Contracts\AuthenticationServiceInterface::class);
    }
}

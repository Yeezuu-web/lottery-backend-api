<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Application\Auth\DTOs\AuthenticateUserCommand;
use App\Application\Auth\DTOs\LogoutUserCommand;
use App\Application\Auth\DTOs\RefreshTokenCommand;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Agent\Models\Agent;
use App\Domain\Auth\Contracts\AuthenticationDomainServiceInterface;
use App\Domain\Auth\Contracts\TokenServiceInterface;
use App\Domain\Auth\ValueObjects\JWTToken;
use App\Domain\Auth\ValueObjects\TokenPair;
use App\Infrastructure\Auth\Contracts\AuthenticationServiceInterface;
use Carbon\Carbon;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;

final class AuthTestHelper
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
        string $status = 'active',
        bool $isActive = true
    ): Agent {
        // For non-company agents, provide a parent ID
        $uplineId = ($agentType === 'company') ? null : 1;

        return Agent::create(
            $id,
            $username,
            $agentType,
            $uplineId,
            $name,
            $email,
            $status,
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
        ?DateTimeImmutable $expiresAt = null
    ): JWTToken {
        $expiresAt ??= (new DateTimeImmutable())->modify('+1 hour');

        $payload = [
            'iss' => 'lottery-api',
            'aud' => $audience,
            'agent_id' => $agentId,
            'username' => 'A',
            'email' => 'test@example.com',
            'agent_type' => 'company',
            'permissions' => ['read', 'write'],
            'iat' => Carbon::now()->timestamp,
            'exp' => $expiresAt,
        ];

        return new JWTToken($token, $payload, $expiresAt);
    }

    /**
     * Create a test expired JWT token
     */
    public static function createExpiredJWTToken(
        string $token = 'expired-jwt-token',
        int $agentId = 123,
        string $audience = 'upline'
    ): JWTToken {
        return self::createTestJWTToken($token, $agentId, $audience, Carbon::now()->subHour()->toDateTimeImmutable());
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
        $refreshTokenObj = self::createTestJWTToken($refreshToken, $agentId, $audience, Carbon::now()->addWeek()->toDateTimeImmutable());

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
        $token ??= self::createTestJWTToken('test-token', 123, $audience);

        return new LogoutUserCommand($token, $audience);
    }

    /**
     * Create mock agent repository
     */
    public static function createMockAgentRepository(): MockInterface
    {
        return Mockery::mock(AgentRepositoryInterface::class);
    }

    /**
     * Create mock token service
     */
    public static function createMockTokenService(): MockInterface
    {
        return Mockery::mock(TokenServiceInterface::class);
    }

    /**
     * Create mock authentication domain service
     */
    public static function createMockAuthDomainService(): MockInterface
    {
        return Mockery::mock(AuthenticationDomainServiceInterface::class);
    }

    /**
     * Create mock authentication infrastructure service
     */
    public static function createMockAuthInfrastructureService(): MockInterface
    {
        return Mockery::mock(AuthenticationServiceInterface::class);
    }
}

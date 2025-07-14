<?php

namespace Tests\Unit\Infrastructure\Auth\Services;

use App\Domain\Auth\Contracts\TokenServiceInterface;
use App\Infrastructure\Auth\Services\AuthenticationService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Helpers\AuthTestHelper;
use Tests\TestCase;

class AuthenticationServiceTest extends TestCase
{
    private $tokenService;

    private $authService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenService = Mockery::mock(TokenServiceInterface::class);
        $this->authService = new AuthenticationService($this->tokenService);

        // Mock config
        config(['jwt.blacklist.enabled' => true]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_store_refresh_token_when_blacklist_enabled()
    {
        // Arrange
        $refreshToken = AuthTestHelper::createTestJWTToken('refresh-token', 123, 'upline');
        $expectedKey = 'jwt:token:upline:'.hash('sha256', 'refresh-token');
        $expectedTtl = 3600; // 1 hour

        $this->tokenService->shouldReceive('getTokenTTL')
            ->once()
            ->with($refreshToken)
            ->andReturn($expectedTtl);

        Cache::shouldReceive('put')
            ->once()
            ->with($expectedKey, Mockery::type('array'), $expectedTtl);

        // Act
        $this->authService->storeRefreshToken($refreshToken);

        // Assert - Method should complete without exceptions
        $this->assertTrue(true);
    }

    public function test_store_refresh_token_when_blacklist_disabled()
    {
        // Arrange
        config(['jwt.blacklist.enabled' => false]);
        $refreshToken = AuthTestHelper::createTestJWTToken('refresh-token', 123, 'upline');

        // Should not call cache or token service when blacklist is disabled
        Cache::shouldReceive('put')->never();
        $this->tokenService->shouldReceive('getTokenTTL')->never();

        // Act
        $this->authService->storeRefreshToken($refreshToken);

        // Assert - Method should complete without exceptions
        $this->assertTrue(true);
    }

    public function test_blacklist_token_when_blacklist_enabled()
    {
        // Arrange
        $token = AuthTestHelper::createTestJWTToken('access-token', 123, 'upline');
        $expectedKey = 'jwt:blacklist:upline:'.hash('sha256', 'access-token');
        $expectedTtl = 3600; // 1 hour

        $this->tokenService->shouldReceive('getTokenTTL')
            ->once()
            ->with($token)
            ->andReturn($expectedTtl);

        Cache::shouldReceive('put')
            ->once()
            ->with($expectedKey, Mockery::type('array'), $expectedTtl)
            ->andReturn(true);

        // Act
        $result = $this->authService->blacklistToken($token);

        // Assert
        $this->assertTrue($result);
    }

    public function test_blacklist_token_when_blacklist_disabled()
    {
        // Arrange
        config(['jwt.blacklist.enabled' => false]);
        $token = AuthTestHelper::createTestJWTToken('access-token', 123, 'upline');

        // Should not call cache or token service when blacklist is disabled
        Cache::shouldReceive('put')->never();
        $this->tokenService->shouldReceive('getTokenTTL')->never();

        // Act
        $result = $this->authService->blacklistToken($token);

        // Assert
        $this->assertTrue($result);
    }

    public function test_is_token_blacklisted_when_blacklist_enabled_and_token_is_blacklisted()
    {
        // Arrange
        $token = AuthTestHelper::createTestJWTToken('blacklisted-token', 123, 'upline');
        $expectedKey = 'jwt:blacklist:upline:'.hash('sha256', 'blacklisted-token');

        Cache::shouldReceive('has')
            ->once()
            ->with($expectedKey)
            ->andReturn(true);

        // Act
        $result = $this->authService->isTokenBlacklisted($token);

        // Assert
        $this->assertTrue($result);
    }

    public function test_is_token_blacklisted_when_blacklist_enabled_and_token_is_not_blacklisted()
    {
        // Arrange
        $token = AuthTestHelper::createTestJWTToken('clean-token', 123, 'upline');
        $expectedKey = 'jwt:blacklist:upline:'.hash('sha256', 'clean-token');

        Cache::shouldReceive('has')
            ->once()
            ->with($expectedKey)
            ->andReturn(false);

        // Act
        $result = $this->authService->isTokenBlacklisted($token);

        // Assert
        $this->assertFalse($result);
    }

    public function test_is_token_blacklisted_when_blacklist_disabled()
    {
        // Arrange
        config(['jwt.blacklist.enabled' => false]);
        $token = AuthTestHelper::createTestJWTToken('any-token', 123, 'upline');

        // Should not call cache when blacklist is disabled
        Cache::shouldReceive('has')->never();

        // Act
        $result = $this->authService->isTokenBlacklisted($token);

        // Assert
        $this->assertFalse($result);
    }

    public function test_verify_token_with_valid_token()
    {
        // Arrange
        $tokenString = 'valid-jwt-token';
        $audience = 'upline';
        $decodedToken = AuthTestHelper::createTestJWTToken($tokenString, 123, $audience);
        $blacklistKey = 'jwt:blacklist:upline:'.hash('sha256', $tokenString);

        $this->tokenService->shouldReceive('decodeToken')
            ->once()
            ->with($tokenString, $audience)
            ->andReturn($decodedToken);

        Cache::shouldReceive('has')
            ->once()
            ->with($blacklistKey)
            ->andReturn(false);

        // Act
        $result = $this->authService->verifyToken($tokenString, $audience);

        // Assert
        $this->assertEquals($decodedToken, $result);
    }

    public function test_verify_token_with_invalid_token()
    {
        // Arrange
        $tokenString = 'invalid-jwt-token';
        $audience = 'upline';

        $this->tokenService->shouldReceive('decodeToken')
            ->once()
            ->with($tokenString, $audience)
            ->andReturn(null);

        // Act
        $result = $this->authService->verifyToken($tokenString, $audience);

        // Assert
        $this->assertNull($result);
    }

    public function test_verify_token_with_blacklisted_token()
    {
        // Arrange
        $tokenString = 'blacklisted-jwt-token';
        $audience = 'upline';
        $decodedToken = AuthTestHelper::createTestJWTToken($tokenString, 123, $audience);
        $blacklistKey = 'jwt:blacklist:upline:'.hash('sha256', $tokenString);

        $this->tokenService->shouldReceive('decodeToken')
            ->once()
            ->with($tokenString, $audience)
            ->andReturn($decodedToken);

        Cache::shouldReceive('has')
            ->once()
            ->with($blacklistKey)
            ->andReturn(true);

        // Act
        $result = $this->authService->verifyToken($tokenString, $audience);

        // Assert
        $this->assertNull($result);
    }

    public function test_verify_token_handles_exceptions()
    {
        // Arrange
        $tokenString = 'problematic-jwt-token';
        $audience = 'upline';

        $this->tokenService->shouldReceive('decodeToken')
            ->once()
            ->with($tokenString, $audience)
            ->andThrow(new \Exception('Token decode failed'));

        // Act
        $result = $this->authService->verifyToken($tokenString, $audience);

        // Assert
        $this->assertNull($result);
    }

    public function test_find_refresh_token_by_agent_returns_null()
    {
        // Arrange
        $agentId = 123;
        $audience = 'upline';

        // Act
        $result = $this->authService->findRefreshTokenByAgent($agentId, $audience);

        // Assert
        $this->assertNull($result);
    }

    public function test_clear_session_data()
    {
        // Arrange
        $agentId = 123;
        $expectedKey = 'session:agent:123';

        Cache::shouldReceive('forget')
            ->once()
            ->with($expectedKey);

        // Act
        $this->authService->clearSessionData($agentId);

        // Assert - Method should complete without exceptions
        $this->assertTrue(true);
    }

    public function test_cache_key_generation_for_tokens()
    {
        // Arrange
        $token = AuthTestHelper::createTestJWTToken('test-token', 123, 'upline');
        $expectedTokenKey = 'jwt:token:upline:'.hash('sha256', 'test-token');
        $expectedBlacklistKey = 'jwt:blacklist:upline:'.hash('sha256', 'test-token');

        $this->tokenService->shouldReceive('getTokenTTL')
            ->twice()
            ->with($token)
            ->andReturn(3600);

        Cache::shouldReceive('put')
            ->once()
            ->with($expectedTokenKey, Mockery::type('array'), 3600);

        Cache::shouldReceive('put')
            ->once()
            ->with($expectedBlacklistKey, Mockery::type('array'), 3600)
            ->andReturn(true);

        // Act
        $this->authService->storeRefreshToken($token);
        $this->authService->blacklistToken($token);

        // Assert - Methods should complete without exceptions
        $this->assertTrue(true);
    }

    public function test_stored_token_data_structure()
    {
        // Arrange
        $refreshToken = AuthTestHelper::createTestJWTToken('refresh-token', 123, 'upline');
        $expectedKey = 'jwt:token:upline:'.hash('sha256', 'refresh-token');
        $expectedTtl = 3600;

        $this->tokenService->shouldReceive('getTokenTTL')
            ->once()
            ->with($refreshToken)
            ->andReturn($expectedTtl);

        Cache::shouldReceive('put')
            ->once()
            ->with($expectedKey, Mockery::on(function ($data) {
                return is_array($data) &&
                       isset($data['agent_id']) &&
                       isset($data['audience']) &&
                       isset($data['created_at']) &&
                       $data['agent_id'] === 123 &&
                       $data['audience'] === 'upline';
            }), $expectedTtl);

        // Act
        $this->authService->storeRefreshToken($refreshToken);

        // Assert - Method should complete without exceptions
        $this->assertTrue(true);
    }

    public function test_blacklisted_token_data_structure()
    {
        // Arrange
        $token = AuthTestHelper::createTestJWTToken('access-token', 123, 'upline');
        $expectedKey = 'jwt:blacklist:upline:'.hash('sha256', 'access-token');
        $expectedTtl = 3600;

        $this->tokenService->shouldReceive('getTokenTTL')
            ->once()
            ->with($token)
            ->andReturn($expectedTtl);

        Cache::shouldReceive('put')
            ->once()
            ->with($expectedKey, Mockery::on(function ($data) {
                return is_array($data) &&
                       isset($data['blacklisted_at']) &&
                       isset($data['agent_id']) &&
                       isset($data['audience']) &&
                       $data['agent_id'] === 123 &&
                       $data['audience'] === 'upline';
            }), $expectedTtl)
            ->andReturn(true);

        // Act
        $result = $this->authService->blacklistToken($token);

        // Assert
        $this->assertTrue($result);
    }
}

<?php

declare(strict_types=1);
use App\Domain\Auth\Contracts\TokenServiceInterface;
use App\Infrastructure\Auth\Services\AuthenticationService;
use Illuminate\Support\Facades\Cache;
use Tests\Helpers\AuthTestHelper;

beforeEach(function (): void {
    $this->tokenService = Mockery::mock(TokenServiceInterface::class);
    $this->authService = new AuthenticationService($this->tokenService);

    // Mock config
    config(['jwt.blacklist.enabled' => true]);
});
afterEach(function (): void {
    Mockery::close();
});
test('store refresh token when blacklist enabled', function (): void {
    // Arrange
    $refreshToken = AuthTestHelper::createTestJWTToken('refresh-token', 123, 'upline');
    $expectedKey = 'jwt:token:upline:'.hash('sha256', 'refresh-token');
    $expectedTtl = 3600;

    // 1 hour
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
    expect(true)->toBeTrue();
});
test('store refresh token when blacklist disabled', function (): void {
    // Arrange
    config(['jwt.blacklist.enabled' => false]);
    $refreshToken = AuthTestHelper::createTestJWTToken('refresh-token', 123, 'upline');

    // Should not call cache or token service when blacklist is disabled
    Cache::shouldReceive('put')->never();
    $this->tokenService->shouldReceive('getTokenTTL')->never();

    // Act
    $this->authService->storeRefreshToken($refreshToken);

    // Assert - Method should complete without exceptions
    expect(true)->toBeTrue();
});
test('blacklist token when blacklist enabled', function (): void {
    // Arrange
    $token = AuthTestHelper::createTestJWTToken('access-token', 123, 'upline');
    $expectedKey = 'jwt:blacklist:upline:'.hash('sha256', 'access-token');
    $expectedTtl = 3600;

    // 1 hour
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
    expect($result)->toBeTrue();
});
test('blacklist token when blacklist disabled', function (): void {
    // Arrange
    config(['jwt.blacklist.enabled' => false]);
    $token = AuthTestHelper::createTestJWTToken('access-token', 123, 'upline');

    // Should not call cache or token service when blacklist is disabled
    Cache::shouldReceive('put')->never();
    $this->tokenService->shouldReceive('getTokenTTL')->never();

    // Act
    $result = $this->authService->blacklistToken($token);

    // Assert
    expect($result)->toBeTrue();
});
test('is token blacklisted when blacklist enabled and token is blacklisted', function (): void {
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
    expect($result)->toBeTrue();
});
test('is token blacklisted when blacklist enabled and token is not blacklisted', function (): void {
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
    expect($result)->toBeFalse();
});
test('is token blacklisted when blacklist disabled', function (): void {
    // Arrange
    config(['jwt.blacklist.enabled' => false]);
    $token = AuthTestHelper::createTestJWTToken('any-token', 123, 'upline');

    // Should not call cache when blacklist is disabled
    Cache::shouldReceive('has')->never();

    // Act
    $result = $this->authService->isTokenBlacklisted($token);

    // Assert
    expect($result)->toBeFalse();
});
test('verify token with valid token', function (): void {
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
    expect($result)->toEqual($decodedToken);
});
test('verify token with invalid token', function (): void {
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
    expect($result)->toBeNull();
});
test('verify token with blacklisted token', function (): void {
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
    expect($result)->toBeNull();
});
test('verify token handles exceptions', function (): void {
    // Arrange
    $tokenString = 'problematic-jwt-token';
    $audience = 'upline';

    $this->tokenService->shouldReceive('decodeToken')
        ->once()
        ->with($tokenString, $audience)
        ->andThrow(new Exception('Token decode failed'));

    // Act
    $result = $this->authService->verifyToken($tokenString, $audience);

    // Assert
    expect($result)->toBeNull();
});
test('find refresh token by agent returns null', function (): void {
    // Arrange
    $agentId = 123;
    $audience = 'upline';

    // Act
    $result = $this->authService->findRefreshTokenByAgent($agentId, $audience);

    // Assert
    expect($result)->toBeNull();
});
test('clear session data', function (): void {
    // Arrange
    $agentId = 123;
    $expectedKey = 'session:agent:123';

    Cache::shouldReceive('forget')
        ->once()
        ->with($expectedKey);

    // Act
    $this->authService->clearSessionData($agentId);

    // Assert - Method should complete without exceptions
    expect(true)->toBeTrue();
});
test('cache key generation for tokens', function (): void {
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
    expect(true)->toBeTrue();
});
test('stored token data structure', function (): void {
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
        ->with($expectedKey, Mockery::on(fn ($data): bool => is_array($data) &&
               isset($data['agent_id']) &&
               isset($data['audience']) &&
               isset($data['created_at']) &&
               $data['agent_id'] === 123 &&
               $data['audience'] === 'upline'), $expectedTtl);

    // Act
    $this->authService->storeRefreshToken($refreshToken);

    // Assert - Method should complete without exceptions
    expect(true)->toBeTrue();
});
test('blacklisted token data structure', function (): void {
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
        ->with($expectedKey, Mockery::on(fn ($data): bool => is_array($data) &&
               isset($data['blacklisted_at']) &&
               isset($data['agent_id']) &&
               isset($data['audience']) &&
               $data['agent_id'] === 123 &&
               $data['audience'] === 'upline'), $expectedTtl)
        ->andReturn(true);

    // Act
    $result = $this->authService->blacklistToken($token);

    // Assert
    expect($result)->toBeTrue();
});

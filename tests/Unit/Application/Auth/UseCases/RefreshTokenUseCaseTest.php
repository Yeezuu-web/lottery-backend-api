<?php

declare(strict_types=1);
use App\Application\Auth\DTOs\AuthenticateUserResponse;
use App\Application\Auth\UseCases\RefreshTokenUseCase;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Auth\Contracts\AuthenticationDomainServiceInterface;
use App\Domain\Auth\Contracts\TokenServiceInterface;
use App\Domain\Auth\Exceptions\AuthenticationException;
use App\Infrastructure\Auth\Contracts\AuthenticationServiceInterface;
use Tests\Helpers\AuthTestHelper;

beforeEach(function (): void {
    $this->agentRepository = Mockery::mock(AgentRepositoryInterface::class);
    $this->tokenService = Mockery::mock(TokenServiceInterface::class);
    $this->authDomainService = Mockery::mock(AuthenticationDomainServiceInterface::class);
    $this->authInfrastructureService = Mockery::mock(AuthenticationServiceInterface::class);

    $this->useCase = new RefreshTokenUseCase(
        $this->agentRepository,
        $this->tokenService,
        $this->authDomainService,
        $this->authInfrastructureService
    );
});
afterEach(function (): void {
    Mockery::close();
});
test('successful token refresh', function (): void {
    // Arrange
    $command = AuthTestHelper::createRefreshTokenCommand();
    $refreshToken = AuthTestHelper::createTestJWTToken('old-refresh-token', 123, 'upline');
    $agent = AuthTestHelper::createTestAgent();
    $newTokenPair = AuthTestHelper::createTestTokenPair('new-access', 'new-refresh');

    // Mock expectations
    $this->tokenService->shouldReceive('decodeToken')
        ->once()
        ->with('test-refresh-token', 'upline')
        ->andReturn($refreshToken);

    $this->authInfrastructureService->shouldReceive('isTokenBlacklisted')
        ->once()
        ->with($refreshToken)
        ->andReturn(false);

    $this->agentRepository->shouldReceive('findById')
        ->once()
        ->with(123)
        ->andReturn($agent);

    $this->authDomainService->shouldReceive('validateAuthentication')
        ->once()
        ->with($agent, 'upline');

    $this->tokenService->shouldReceive('generateTokenPair')
        ->once()
        ->with($agent, 'upline')
        ->andReturn($newTokenPair);

    $this->authInfrastructureService->shouldReceive('blacklistToken')
        ->once()
        ->with($refreshToken);

    $this->authInfrastructureService->shouldReceive('storeRefreshToken')
        ->once()
        ->with($newTokenPair->refreshToken());

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeInstanceOf(AuthenticateUserResponse::class);
    expect($result->agent)->toEqual($agent);
    expect($result->tokenPair)->toEqual($newTokenPair);
    expect($result->success)->toBeTrue();
    expect($result->message)->toEqual('Token refreshed successfully');
});
test('refresh fails with invalid token', function (): void {
    // Arrange
    $command = AuthTestHelper::createRefreshTokenCommand();

    $this->tokenService->shouldReceive('decodeToken')
        ->once()
        ->with('test-refresh-token', 'upline')
        ->andReturn(null);

    // Act & Assert
    $this->expectException(AuthenticationException::class);
    $this->expectExceptionMessage('Invalid refresh token');

    $this->useCase->execute($command);
});
test('refresh fails with expired token', function (): void {
    // Arrange
    $command = AuthTestHelper::createRefreshTokenCommand();
    $expiredToken = AuthTestHelper::createExpiredJWTToken('expired-refresh-token', 123, 'upline');

    $this->tokenService->shouldReceive('decodeToken')
        ->once()
        ->with('test-refresh-token', 'upline')
        ->andReturn($expiredToken);

    // Act & Assert
    $this->expectException(AuthenticationException::class);
    $this->expectExceptionMessage('Refresh token has expired');

    $this->useCase->execute($command);
});
test('refresh fails with blacklisted token', function (): void {
    // Arrange
    $command = AuthTestHelper::createRefreshTokenCommand();
    $refreshToken = AuthTestHelper::createTestJWTToken('blacklisted-refresh-token', 123, 'upline');

    $this->tokenService->shouldReceive('decodeToken')
        ->once()
        ->with('test-refresh-token', 'upline')
        ->andReturn($refreshToken);

    $this->authInfrastructureService->shouldReceive('isTokenBlacklisted')
        ->once()
        ->with($refreshToken)
        ->andReturn(true);

    // Act & Assert
    $this->expectException(AuthenticationException::class);
    $this->expectExceptionMessage('Invalid refresh token');

    $this->useCase->execute($command);
});
test('refresh fails when agent not found', function (): void {
    // Arrange
    $command = AuthTestHelper::createRefreshTokenCommand();
    $refreshToken = AuthTestHelper::createTestJWTToken('valid-refresh-token', 999, 'upline');

    $this->tokenService->shouldReceive('decodeToken')
        ->once()
        ->with('test-refresh-token', 'upline')
        ->andReturn($refreshToken);

    $this->authInfrastructureService->shouldReceive('isTokenBlacklisted')
        ->once()
        ->with($refreshToken)
        ->andReturn(false);

    $this->agentRepository->shouldReceive('findById')
        ->once()
        ->with(999)
        ->andReturn(null);

    // Act & Assert
    $this->expectException(AuthenticationException::class);
    $this->expectExceptionMessage('Invalid refresh token');

    $this->useCase->execute($command);
});
test('refresh fails with domain validation error', function (): void {
    // Arrange
    $command = AuthTestHelper::createRefreshTokenCommand();
    $refreshToken = AuthTestHelper::createTestJWTToken('valid-refresh-token', 123, 'upline');
    $agent = AuthTestHelper::createTestAgent();

    $this->tokenService->shouldReceive('decodeToken')
        ->once()
        ->with('test-refresh-token', 'upline')
        ->andReturn($refreshToken);

    $this->authInfrastructureService->shouldReceive('isTokenBlacklisted')
        ->once()
        ->with($refreshToken)
        ->andReturn(false);

    $this->agentRepository->shouldReceive('findById')
        ->once()
        ->with(123)
        ->andReturn($agent);

    $this->authDomainService->shouldReceive('validateAuthentication')
        ->once()
        ->with($agent, 'upline')
        ->andThrow(new AuthenticationException('Agent not authorized for this audience'));

    // Act & Assert
    $this->expectException(AuthenticationException::class);
    $this->expectExceptionMessage('Agent not authorized for this audience');

    $this->useCase->execute($command);
});
test('refresh with member audience', function (): void {
    // Arrange
    $command = AuthTestHelper::createRefreshTokenCommand('test-refresh-token', 'member');
    $refreshToken = AuthTestHelper::createTestJWTToken('old-refresh-token', 123, 'member');
    $agent = AuthTestHelper::createTestAgent(123, 'AAAAAAAA000', 'test@example.com', 'Test User', 'member');
    $newTokenPair = AuthTestHelper::createTestTokenPair('new-access', 'new-refresh', 123, 'member');

    // Mock expectations
    $this->tokenService->shouldReceive('decodeToken')
        ->once()
        ->with('test-refresh-token', 'member')
        ->andReturn($refreshToken);

    $this->authInfrastructureService->shouldReceive('isTokenBlacklisted')
        ->once()
        ->with($refreshToken)
        ->andReturn(false);

    $this->agentRepository->shouldReceive('findById')
        ->once()
        ->with(123)
        ->andReturn($agent);

    $this->authDomainService->shouldReceive('validateAuthentication')
        ->once()
        ->with($agent, 'member');

    $this->tokenService->shouldReceive('generateTokenPair')
        ->once()
        ->with($agent, 'member')
        ->andReturn($newTokenPair);

    $this->authInfrastructureService->shouldReceive('blacklistToken')
        ->once()
        ->with($refreshToken);

    $this->authInfrastructureService->shouldReceive('storeRefreshToken')
        ->once()
        ->with($newTokenPair->refreshToken());

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeInstanceOf(AuthenticateUserResponse::class);
    expect($result->agent)->toEqual($agent);
    expect($result->tokenPair)->toEqual($newTokenPair);
    expect($result->success)->toBeTrue();
});
test('workflow follows correct sequence', function (): void {
    // Arrange
    $command = AuthTestHelper::createRefreshTokenCommand();
    $refreshToken = AuthTestHelper::createTestJWTToken('old-refresh-token', 123, 'upline');
    $agent = AuthTestHelper::createTestAgent();
    $newTokenPair = AuthTestHelper::createTestTokenPair('new-access', 'new-refresh');

    // Mock expectations in order
    $this->tokenService->shouldReceive('decodeToken')
        ->once()
        ->with('test-refresh-token', 'upline')
        ->andReturn($refreshToken)
        ->ordered();

    $this->authInfrastructureService->shouldReceive('isTokenBlacklisted')
        ->once()
        ->with($refreshToken)
        ->andReturn(false)
        ->ordered();

    $this->agentRepository->shouldReceive('findById')
        ->once()
        ->with(123)
        ->andReturn($agent)
        ->ordered();

    $this->authDomainService->shouldReceive('validateAuthentication')
        ->once()
        ->with($agent, 'upline')
        ->ordered();

    $this->tokenService->shouldReceive('generateTokenPair')
        ->once()
        ->with($agent, 'upline')
        ->andReturn($newTokenPair)
        ->ordered();

    $this->authInfrastructureService->shouldReceive('blacklistToken')
        ->once()
        ->with($refreshToken)
        ->ordered();

    $this->authInfrastructureService->shouldReceive('storeRefreshToken')
        ->once()
        ->with($newTokenPair->refreshToken())
        ->ordered();

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeInstanceOf(AuthenticateUserResponse::class);
});

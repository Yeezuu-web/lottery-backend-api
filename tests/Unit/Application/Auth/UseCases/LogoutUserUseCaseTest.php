<?php

declare(strict_types=1);
use App\Application\Auth\DTOs\OperationResponse;
use App\Application\Auth\UseCases\LogoutUserUseCase;
use App\Domain\Auth\Contracts\TokenServiceInterface;
use App\Domain\Auth\Exceptions\AuthenticationException;
use App\Infrastructure\Auth\Contracts\AuthenticationServiceInterface;
use Tests\Helpers\AuthTestHelper;

beforeEach(function (): void {
    $this->tokenService = Mockery::mock(TokenServiceInterface::class);
    $this->authInfrastructureService = Mockery::mock(AuthenticationServiceInterface::class);

    $this->useCase = new LogoutUserUseCase(
        $this->tokenService,
        $this->authInfrastructureService
    );
});
afterEach(function (): void {
    Mockery::close();
});
test('successful logout', function (): void {
    // Arrange
    $token = AuthTestHelper::createTestJWTToken('valid-access-token', 123, 'upline');
    $refreshToken = AuthTestHelper::createTestJWTToken('valid-refresh-token', 123, 'upline');
    $command = AuthTestHelper::createLogoutUserCommand($token, 'upline');

    // Mock expectations
    $this->authInfrastructureService->shouldReceive('blacklistToken')
        ->once()
        ->with($token);

    $this->authInfrastructureService->shouldReceive('findRefreshTokenByAgent')
        ->once()
        ->with(123, 'upline')
        ->andReturn($refreshToken);

    $this->authInfrastructureService->shouldReceive('blacklistToken')
        ->once()
        ->with($refreshToken);

    $this->authInfrastructureService->shouldReceive('clearSessionData')
        ->once()
        ->with(123);

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeInstanceOf(OperationResponse::class);
    expect($result->success)->toBeTrue();
    expect($result->message)->toEqual('Logout successful');
    expect($result->data)->toEqual([]);
});
test('logout fails with expired token', function (): void {
    // Arrange
    $expiredToken = AuthTestHelper::createExpiredJWTToken('expired-access-token', 123, 'upline');
    $command = AuthTestHelper::createLogoutUserCommand($expiredToken, 'upline');

    // Act & Assert
    $this->expectException(AuthenticationException::class);
    $this->expectExceptionMessage('Token has expired');

    $this->useCase->execute($command);
});
test('logout without refresh token', function (): void {
    // Arrange
    $token = AuthTestHelper::createTestJWTToken('valid-access-token', 123, 'upline');
    $command = AuthTestHelper::createLogoutUserCommand($token, 'upline');

    // Mock expectations
    $this->authInfrastructureService->shouldReceive('blacklistToken')
        ->once()
        ->with($token);

    $this->authInfrastructureService->shouldReceive('findRefreshTokenByAgent')
        ->once()
        ->with(123, 'upline')
        ->andReturn(null);

    // No refresh token found
    $this->authInfrastructureService->shouldReceive('clearSessionData')
        ->once()
        ->with(123);

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeInstanceOf(OperationResponse::class);
    expect($result->success)->toBeTrue();
    expect($result->message)->toEqual('Logout successful');
});
test('logout with member audience', function (): void {
    // Arrange
    $token = AuthTestHelper::createTestJWTToken('valid-access-token', 123, 'member');
    $refreshToken = AuthTestHelper::createTestJWTToken('valid-refresh-token', 123, 'member');
    $command = AuthTestHelper::createLogoutUserCommand($token, 'member');

    // Mock expectations
    $this->authInfrastructureService->shouldReceive('blacklistToken')
        ->once()
        ->with($token);

    $this->authInfrastructureService->shouldReceive('findRefreshTokenByAgent')
        ->once()
        ->with(123, 'member')
        ->andReturn($refreshToken);

    $this->authInfrastructureService->shouldReceive('blacklistToken')
        ->once()
        ->with($refreshToken);

    $this->authInfrastructureService->shouldReceive('clearSessionData')
        ->once()
        ->with(123);

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeInstanceOf(OperationResponse::class);
    expect($result->success)->toBeTrue();
    expect($result->message)->toEqual('Logout successful');
});
test('workflow follows correct sequence', function (): void {
    // Arrange
    $token = AuthTestHelper::createTestJWTToken('valid-access-token', 123, 'upline');
    $refreshToken = AuthTestHelper::createTestJWTToken('valid-refresh-token', 123, 'upline');
    $command = AuthTestHelper::createLogoutUserCommand($token, 'upline');

    // Mock expectations in order
    $this->authInfrastructureService->shouldReceive('blacklistToken')
        ->once()
        ->with($token)
        ->ordered();

    $this->authInfrastructureService->shouldReceive('findRefreshTokenByAgent')
        ->once()
        ->with(123, 'upline')
        ->andReturn($refreshToken)
        ->ordered();

    $this->authInfrastructureService->shouldReceive('blacklistToken')
        ->once()
        ->with($refreshToken)
        ->ordered();

    $this->authInfrastructureService->shouldReceive('clearSessionData')
        ->once()
        ->with(123)
        ->ordered();

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeInstanceOf(OperationResponse::class);
    expect($result->success)->toBeTrue();
});
test('logout handles blacklist failure gracefully', function (): void {
    // Arrange
    $token = AuthTestHelper::createTestJWTToken('valid-access-token', 123, 'upline');
    $command = AuthTestHelper::createLogoutUserCommand($token, 'upline');

    // Mock expectations - blacklisting might fail and exception should bubble up
    $this->authInfrastructureService->shouldReceive('blacklistToken')
        ->once()
        ->with($token)
        ->andThrow(new Exception('Cache service unavailable'));

    // No other methods should be called since the exception is thrown early
    // The use case should let the exception bubble up since the current implementation doesn't catch it
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Cache service unavailable');

    // Act
    $this->useCase->execute($command);
});

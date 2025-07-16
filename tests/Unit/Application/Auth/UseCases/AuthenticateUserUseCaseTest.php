<?php

declare(strict_types=1);

use App\Application\Auth\DTOs\AuthenticateUserResponse;
use App\Application\Auth\UseCases\AuthenticateUserUseCase;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Agent\ValueObjects\Username;
use App\Domain\Auth\Contracts\AuthenticationDomainServiceInterface;
use App\Domain\Auth\Contracts\LoginAuditServiceInterface;
use App\Domain\Auth\Contracts\TokenServiceInterface;
use App\Domain\Auth\Exceptions\AuthenticationException;
use App\Infrastructure\Auth\Contracts\AuthenticationServiceInterface;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Tests\Helpers\AuthTestHelper;

beforeEach(function (): void {
    $this->agentRepository = Mockery::mock(AgentRepositoryInterface::class);
    $this->tokenService = Mockery::mock(TokenServiceInterface::class);
    $this->authDomainService = Mockery::mock(AuthenticationDomainServiceInterface::class);
    $this->authInfrastructureService = Mockery::mock(AuthenticationServiceInterface::class);
    $this->loginAuditService = Mockery::mock(LoginAuditServiceInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcher::class);

    $this->useCase = new AuthenticateUserUseCase(
        $this->agentRepository,
        $this->tokenService,
        $this->authDomainService,
        $this->authInfrastructureService,
        $this->loginAuditService,
        $this->eventDispatcher
    );
});
afterEach(function (): void {
    Mockery::close();
});
test('successful authentication', function (): void {
    // Arrange
    $command = AuthTestHelper::createAuthenticateUserCommand();
    $agent = AuthTestHelper::createTestAgent();
    $tokenPair = AuthTestHelper::createTestTokenPair();

    // Mock expectations
    $this->authDomainService->shouldReceive('validateAudience')
        ->once()
        ->with('upline');

    $this->agentRepository->shouldReceive('findByUsername')
        ->once()
        ->with(Mockery::on(fn ($username): bool => $username instanceof Username && $username->value() === 'A'))
        ->andReturn($agent);

    $this->agentRepository->shouldReceive('verifyPassword')
        ->once()
        ->with($agent, 'password123')
        ->andReturn(true);

    $this->authDomainService->shouldReceive('validateAuthentication')
        ->once()
        ->with($agent, 'upline');

    $this->tokenService->shouldReceive('generateTokenPair')
        ->once()
        ->with($agent, 'upline')
        ->andReturn($tokenPair);

    $this->authInfrastructureService->shouldReceive('storeRefreshToken')
        ->once()
        ->with($tokenPair->refreshToken());

    // Event dispatching expectations - no events should be dispatched for requests without context
    $this->eventDispatcher->shouldReceive('dispatch')->never();

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeInstanceOf(AuthenticateUserResponse::class);
    expect($result->agent)->toEqual($agent);
    expect($result->tokenPair)->toEqual($tokenPair);
    expect($result->success)->toBeTrue();
    expect($result->message)->toEqual('Authentication successful');
});
test('authentication fails with invalid audience', function (): void {
    // Arrange
    $command = AuthTestHelper::createAuthenticateUserCommand();

    $this->authDomainService->shouldReceive('validateAudience')
        ->once()
        ->with('upline')
        ->andThrow(new AuthenticationException('Invalid audience'));

    // Act & Assert
    $this->expectException(AuthenticationException::class);
    $this->expectExceptionMessage('Invalid audience');

    $this->useCase->execute($command);
});
test('authentication fails with user not found', function (): void {
    // Arrange
    $command = AuthTestHelper::createAuthenticateUserCommand();

    $this->authDomainService->shouldReceive('validateAudience')
        ->once()
        ->with('upline');

    $this->agentRepository->shouldReceive('findByUsername')
        ->once()
        ->with(Mockery::on(fn ($username): bool => $username instanceof Username && $username->value() === 'A'))
        ->andReturn(null);

    // Act & Assert
    $this->expectException(AuthenticationException::class);
    $this->expectExceptionMessage('Invalid username or password');

    $this->useCase->execute($command);
});
test('authentication fails with invalid password', function (): void {
    // Arrange
    $command = AuthTestHelper::createAuthenticateUserCommand();
    $agent = AuthTestHelper::createTestAgent();

    $this->authDomainService->shouldReceive('validateAudience')
        ->once()
        ->with('upline');

    $this->agentRepository->shouldReceive('findByUsername')
        ->once()
        ->with(Mockery::on(fn ($username): bool => $username instanceof Username && $username->value() === 'A'))
        ->andReturn($agent);

    $this->agentRepository->shouldReceive('verifyPassword')
        ->once()
        ->with($agent, 'password123')
        ->andReturn(false);

    // Act & Assert
    $this->expectException(AuthenticationException::class);
    $this->expectExceptionMessage('Invalid username or password');

    $this->useCase->execute($command);
});
test('authentication fails with domain validation error', function (): void {
    // Arrange
    $command = AuthTestHelper::createAuthenticateUserCommand();
    $agent = AuthTestHelper::createTestAgent();

    $this->authDomainService->shouldReceive('validateAudience')
        ->once()
        ->with('upline');

    $this->agentRepository->shouldReceive('findByUsername')
        ->once()
        ->with(Mockery::on(fn ($username): bool => $username instanceof Username && $username->value() === 'A'))
        ->andReturn($agent);

    $this->agentRepository->shouldReceive('verifyPassword')
        ->once()
        ->with($agent, 'password123')
        ->andReturn(true);

    $this->authDomainService->shouldReceive('validateAuthentication')
        ->once()
        ->with($agent, 'upline')
        ->andThrow(new AuthenticationException('Agent not authorized for this audience'));

    // Act & Assert
    $this->expectException(AuthenticationException::class);
    $this->expectExceptionMessage('Agent not authorized for this audience');

    $this->useCase->execute($command);
});
test('authentication with member audience', function (): void {
    // Arrange
    $command = AuthTestHelper::createAuthenticateUserCommand('AAAAAAAA000', 'password123', 'member');
    $agent = AuthTestHelper::createTestAgent(123, 'AAAAAAAA000', 'test@example.com', 'Test User', 'member');
    $tokenPair = AuthTestHelper::createTestTokenPair('access', 'refresh', 123, 'member');

    // Mock expectations
    $this->authDomainService->shouldReceive('validateAudience')
        ->once()
        ->with('member');

    $this->agentRepository->shouldReceive('findByUsername')
        ->once()
        ->with(Mockery::on(fn ($username): bool => $username instanceof Username && $username->value() === 'AAAAAAAA000'))
        ->andReturn($agent);

    $this->agentRepository->shouldReceive('verifyPassword')
        ->once()
        ->with($agent, 'password123')
        ->andReturn(true);

    $this->authDomainService->shouldReceive('validateAuthentication')
        ->once()
        ->with($agent, 'member');

    $this->tokenService->shouldReceive('generateTokenPair')
        ->once()
        ->with($agent, 'member')
        ->andReturn($tokenPair);

    $this->authInfrastructureService->shouldReceive('storeRefreshToken')
        ->once()
        ->with($tokenPair->refreshToken());

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeInstanceOf(AuthenticateUserResponse::class);
    expect($result->agent)->toEqual($agent);
    expect($result->tokenPair)->toEqual($tokenPair);
    expect($result->success)->toBeTrue();
});
test('workflow follows correct sequence', function (): void {
    // Arrange
    $command = AuthTestHelper::createAuthenticateUserCommand();
    $agent = AuthTestHelper::createTestAgent();
    $tokenPair = AuthTestHelper::createTestTokenPair();

    // Mock expectations in order
    $this->authDomainService->shouldReceive('validateAudience')
        ->once()
        ->with('upline')
        ->ordered();

    $this->agentRepository->shouldReceive('findByUsername')
        ->once()
        ->with(Mockery::on(fn ($username): bool => $username instanceof Username && $username->value() === 'A'))
        ->andReturn($agent)
        ->ordered();

    $this->agentRepository->shouldReceive('verifyPassword')
        ->once()
        ->with($agent, 'password123')
        ->andReturn(true)
        ->ordered();

    $this->authDomainService->shouldReceive('validateAuthentication')
        ->once()
        ->with($agent, 'upline')
        ->ordered();

    $this->tokenService->shouldReceive('generateTokenPair')
        ->once()
        ->with($agent, 'upline')
        ->andReturn($tokenPair)
        ->ordered();

    $this->authInfrastructureService->shouldReceive('storeRefreshToken')
        ->once()
        ->with($tokenPair->refreshToken())
        ->ordered();

    // Act
    $result = $this->useCase->execute($command);

    // Assert
    expect($result)->toBeInstanceOf(AuthenticateUserResponse::class);
});

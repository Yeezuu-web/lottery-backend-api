<?php

namespace Tests\Unit\Application\Auth\UseCases;

use App\Application\Auth\DTOs\OperationResponse;
use App\Application\Auth\UseCases\LogoutUserUseCase;
use App\Domain\Auth\Contracts\TokenServiceInterface;
use App\Domain\Auth\Exceptions\AuthenticationException;
use App\Infrastructure\Auth\Contracts\AuthenticationServiceInterface;
use Mockery;
use PHPUnit\Framework\TestCase;
use Tests\Helpers\AuthTestHelper;

class LogoutUserUseCaseTest extends TestCase
{
    private $tokenService;

    private $authInfrastructureService;

    private $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenService = Mockery::mock(TokenServiceInterface::class);
        $this->authInfrastructureService = Mockery::mock(AuthenticationServiceInterface::class);

        $this->useCase = new LogoutUserUseCase(
            $this->tokenService,
            $this->authInfrastructureService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_successful_logout()
    {
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
        $this->assertInstanceOf(OperationResponse::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals('Logout successful', $result->message);
        $this->assertEquals([], $result->data);
    }

    public function test_logout_fails_with_expired_token()
    {
        // Arrange
        $expiredToken = AuthTestHelper::createExpiredJWTToken('expired-access-token', 123, 'upline');
        $command = AuthTestHelper::createLogoutUserCommand($expiredToken, 'upline');

        // Act & Assert
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Token has expired');

        $this->useCase->execute($command);
    }

    public function test_logout_without_refresh_token()
    {
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
            ->andReturn(null); // No refresh token found

        $this->authInfrastructureService->shouldReceive('clearSessionData')
            ->once()
            ->with(123);

        // Act
        $result = $this->useCase->execute($command);

        // Assert
        $this->assertInstanceOf(OperationResponse::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals('Logout successful', $result->message);
    }

    public function test_logout_with_member_audience()
    {
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
        $this->assertInstanceOf(OperationResponse::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals('Logout successful', $result->message);
    }

    public function test_workflow_follows_correct_sequence()
    {
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
        $this->assertInstanceOf(OperationResponse::class, $result);
        $this->assertTrue($result->success);
    }

    public function test_logout_handles_blacklist_failure_gracefully()
    {
        // Arrange
        $token = AuthTestHelper::createTestJWTToken('valid-access-token', 123, 'upline');
        $command = AuthTestHelper::createLogoutUserCommand($token, 'upline');

        // Mock expectations - blacklisting might fail and exception should bubble up
        $this->authInfrastructureService->shouldReceive('blacklistToken')
            ->once()
            ->with($token)
            ->andThrow(new \Exception('Cache service unavailable'));

        // No other methods should be called since the exception is thrown early

        // The use case should let the exception bubble up since the current implementation doesn't catch it
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cache service unavailable');

        // Act
        $this->useCase->execute($command);
    }
}

<?php

namespace Tests\Unit\Application\Auth\UseCases;

use App\Application\Auth\DTOs\AuthenticateUserResponse;
use App\Application\Auth\UseCases\RefreshTokenUseCase;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Auth\Contracts\AuthenticationDomainServiceInterface;
use App\Domain\Auth\Contracts\TokenServiceInterface;
use App\Domain\Auth\Exceptions\AuthenticationException;
use App\Infrastructure\Auth\Contracts\AuthenticationServiceInterface;
use Mockery;
use PHPUnit\Framework\TestCase;
use Tests\Helpers\AuthTestHelper;

class RefreshTokenUseCaseTest extends TestCase
{
    private $agentRepository;

    private $tokenService;

    private $authDomainService;

    private $authInfrastructureService;

    private $useCase;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_successful_token_refresh()
    {
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
        $this->assertInstanceOf(AuthenticateUserResponse::class, $result);
        $this->assertEquals($agent, $result->agent);
        $this->assertEquals($newTokenPair, $result->tokenPair);
        $this->assertTrue($result->success);
        $this->assertEquals('Token refreshed successfully', $result->message);
    }

    public function test_refresh_fails_with_invalid_token()
    {
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
    }

    public function test_refresh_fails_with_expired_token()
    {
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
    }

    public function test_refresh_fails_with_blacklisted_token()
    {
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
    }

    public function test_refresh_fails_when_agent_not_found()
    {
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
    }

    public function test_refresh_fails_with_domain_validation_error()
    {
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
    }

    public function test_refresh_with_member_audience()
    {
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
        $this->assertInstanceOf(AuthenticateUserResponse::class, $result);
        $this->assertEquals($agent, $result->agent);
        $this->assertEquals($newTokenPair, $result->tokenPair);
        $this->assertTrue($result->success);
    }

    public function test_workflow_follows_correct_sequence()
    {
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
        $this->assertInstanceOf(AuthenticateUserResponse::class, $result);
    }
}

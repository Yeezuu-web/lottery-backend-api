<?php

namespace Tests\Unit\Application\Auth\UseCases;

use App\Application\Auth\DTOs\AuthenticateUserResponse;
use App\Application\Auth\UseCases\AuthenticateUserUseCase;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Agent\ValueObjects\Username;
use App\Domain\Auth\Contracts\AuthenticationDomainServiceInterface;
use App\Domain\Auth\Contracts\TokenServiceInterface;
use App\Domain\Auth\Exceptions\AuthenticationException;
use App\Infrastructure\Auth\Contracts\AuthenticationServiceInterface;
use Mockery;
use PHPUnit\Framework\TestCase;
use Tests\Helpers\AuthTestHelper;

class AuthenticateUserUseCaseTest extends TestCase
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

        $this->useCase = new AuthenticateUserUseCase(
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

    public function test_successful_authentication()
    {
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
            ->with(Mockery::on(function ($username) {
                return $username instanceof Username && $username->value() === 'A';
            }))
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

        // Act
        $result = $this->useCase->execute($command);

        // Assert
        $this->assertInstanceOf(AuthenticateUserResponse::class, $result);
        $this->assertEquals($agent, $result->agent);
        $this->assertEquals($tokenPair, $result->tokenPair);
        $this->assertTrue($result->success);
        $this->assertEquals('Authentication successful', $result->message);
    }

    public function test_authentication_fails_with_invalid_audience()
    {
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
    }

    public function test_authentication_fails_with_user_not_found()
    {
        // Arrange
        $command = AuthTestHelper::createAuthenticateUserCommand();

        $this->authDomainService->shouldReceive('validateAudience')
            ->once()
            ->with('upline');

        $this->agentRepository->shouldReceive('findByUsername')
            ->once()
            ->with(Mockery::on(function ($username) {
                return $username instanceof Username && $username->value() === 'A';
            }))
            ->andReturn(null);

        // Act & Assert
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid username or password');

        $this->useCase->execute($command);
    }

    public function test_authentication_fails_with_invalid_password()
    {
        // Arrange
        $command = AuthTestHelper::createAuthenticateUserCommand();
        $agent = AuthTestHelper::createTestAgent();

        $this->authDomainService->shouldReceive('validateAudience')
            ->once()
            ->with('upline');

        $this->agentRepository->shouldReceive('findByUsername')
            ->once()
            ->with(Mockery::on(function ($username) {
                return $username instanceof Username && $username->value() === 'A';
            }))
            ->andReturn($agent);

        $this->agentRepository->shouldReceive('verifyPassword')
            ->once()
            ->with($agent, 'password123')
            ->andReturn(false);

        // Act & Assert
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid username or password');

        $this->useCase->execute($command);
    }

    public function test_authentication_fails_with_domain_validation_error()
    {
        // Arrange
        $command = AuthTestHelper::createAuthenticateUserCommand();
        $agent = AuthTestHelper::createTestAgent();

        $this->authDomainService->shouldReceive('validateAudience')
            ->once()
            ->with('upline');

        $this->agentRepository->shouldReceive('findByUsername')
            ->once()
            ->with(Mockery::on(function ($username) {
                return $username instanceof Username && $username->value() === 'A';
            }))
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
    }

    public function test_authentication_with_member_audience()
    {
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
            ->with(Mockery::on(function ($username) {
                return $username instanceof Username && $username->value() === 'AAAAAAAA000';
            }))
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
        $this->assertInstanceOf(AuthenticateUserResponse::class, $result);
        $this->assertEquals($agent, $result->agent);
        $this->assertEquals($tokenPair, $result->tokenPair);
        $this->assertTrue($result->success);
    }

    public function test_workflow_follows_correct_sequence()
    {
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
            ->with(Mockery::on(function ($username) {
                return $username instanceof Username && $username->value() === 'A';
            }))
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
        $this->assertInstanceOf(AuthenticateUserResponse::class, $result);
    }
}

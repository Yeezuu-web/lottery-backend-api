<?php

namespace App\Application\Auth\UseCases;

use App\Application\Auth\DTOs\AuthenticateUserResponse;
use App\Application\Auth\DTOs\RefreshTokenCommand;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Auth\Contracts\AuthenticationDomainServiceInterface;
use App\Domain\Auth\Contracts\TokenServiceInterface;
use App\Domain\Auth\Exceptions\AuthenticationException;
use App\Infrastructure\Auth\Contracts\AuthenticationServiceInterface;

final class RefreshTokenUseCase
{
    private readonly AgentRepositoryInterface $agentRepository;

    private readonly TokenServiceInterface $tokenService;

    private readonly AuthenticationDomainServiceInterface $authDomainService;

    private readonly AuthenticationServiceInterface $authInfrastructureService;

    public function __construct(
        AgentRepositoryInterface $agentRepository,
        TokenServiceInterface $tokenService,
        AuthenticationDomainServiceInterface $authDomainService,
        AuthenticationServiceInterface $authInfrastructureService
    ) {
        $this->agentRepository = $agentRepository;
        $this->tokenService = $tokenService;
        $this->authDomainService = $authDomainService;
        $this->authInfrastructureService = $authInfrastructureService;
    }

    /**
     * Execute token refresh workflow
     */
    public function execute(RefreshTokenCommand $command): AuthenticateUserResponse
    {
        // 1. Decode and validate refresh token (infrastructure operation)
        $refreshToken = $this->tokenService->decodeToken($command->refreshToken, $command->audience);
        if (! $refreshToken) {
            throw AuthenticationException::invalidRefreshToken();
        }

        // 2. Check if token is expired (domain validation)
        if ($refreshToken->isExpired()) {
            throw AuthenticationException::refreshTokenExpired();
        }

        // 3. Check if token is blacklisted (infrastructure operation)
        if ($this->authInfrastructureService->isTokenBlacklisted($refreshToken)) {
            throw AuthenticationException::invalidRefreshToken();
        }

        // 4. Find agent by ID from token (infrastructure operation)
        $agent = $this->agentRepository->findById($refreshToken->getAgentId());
        if (! $agent) {
            throw AuthenticationException::invalidRefreshToken();
        }

        // 5. Apply domain business rules (domain validation)
        $this->authDomainService->validateAuthentication($agent, $command->audience);

        // 6. Generate new token pair (infrastructure operation)
        $newTokenPair = $this->tokenService->generateTokenPair($agent, $command->audience);

        // 7. Blacklist old refresh token (infrastructure operation)
        $this->authInfrastructureService->blacklistToken($refreshToken);

        // 8. Store new refresh token (infrastructure operation)
        $this->authInfrastructureService->storeRefreshToken($newTokenPair->refreshToken());

        // 9. Return successful response
        return AuthenticateUserResponse::success($agent, $newTokenPair, 'Token refreshed successfully');
    }
}

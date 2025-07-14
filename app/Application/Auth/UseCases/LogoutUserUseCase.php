<?php

namespace App\Application\Auth\UseCases;

use App\Application\Auth\DTOs\LogoutUserCommand;
use App\Application\Auth\DTOs\OperationResponse;
use App\Domain\Auth\Contracts\TokenServiceInterface;
use App\Domain\Auth\Exceptions\AuthenticationException;
use App\Infrastructure\Auth\Contracts\AuthenticationServiceInterface;

final class LogoutUserUseCase
{
    private readonly TokenServiceInterface $tokenService;

    private readonly AuthenticationServiceInterface $authInfrastructureService;

    public function __construct(
        TokenServiceInterface $tokenService,
        AuthenticationServiceInterface $authInfrastructureService
    ) {
        $this->tokenService = $tokenService;
        $this->authInfrastructureService = $authInfrastructureService;
    }

    /**
     * Execute logout workflow
     */
    public function execute(LogoutUserCommand $command): OperationResponse
    {
        // 1. Validate token (infrastructure operation)
        if ($command->token->isExpired()) {
            throw AuthenticationException::tokenExpired();
        }

        // 2. Blacklist the access token (infrastructure operation)
        $this->authInfrastructureService->blacklistToken($command->token);

        // 3. Find and blacklist associated refresh token (infrastructure operation)
        $refreshToken = $this->authInfrastructureService->findRefreshTokenByAgent(
            $command->token->getAgentId(),
            $command->audience
        );

        if ($refreshToken) {
            $this->authInfrastructureService->blacklistToken($refreshToken);
        }

        // 4. Clear any session data (infrastructure operation)
        $this->authInfrastructureService->clearSessionData($command->token->getAgentId());

        // 5. Return successful response
        return OperationResponse::success('Logout successful');
    }
}

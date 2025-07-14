<?php

declare(strict_types=1);

namespace App\Application\Auth\UseCases;

use App\Application\Auth\DTOs\LogoutUserCommand;
use App\Application\Auth\DTOs\OperationResponse;
use App\Domain\Auth\Contracts\TokenServiceInterface;
use App\Domain\Auth\Exceptions\AuthenticationException;
use App\Infrastructure\Auth\Contracts\AuthenticationServiceInterface;

final readonly class LogoutUserUseCase
{
    public function __construct(TokenServiceInterface $tokenService, private AuthenticationServiceInterface $authInfrastructureService) {}

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

        if ($refreshToken instanceof \App\Domain\Auth\ValueObjects\JWTToken) {
            $this->authInfrastructureService->blacklistToken($refreshToken);
        }

        // 4. Clear any session data (infrastructure operation)
        $this->authInfrastructureService->clearSessionData($command->token->getAgentId());

        // 5. Return successful response
        return OperationResponse::success('Logout successful');
    }
}

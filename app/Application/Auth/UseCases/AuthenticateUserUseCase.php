<?php

declare(strict_types=1);

namespace App\Application\Auth\UseCases;

use App\Application\Auth\DTOs\AuthenticateUserCommand;
use App\Application\Auth\DTOs\AuthenticateUserResponse;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Agent\ValueObjects\Username;
use App\Domain\Auth\Contracts\AuthenticationDomainServiceInterface;
use App\Domain\Auth\Contracts\TokenServiceInterface;
use App\Domain\Auth\Exceptions\AuthenticationException;
use App\Infrastructure\Auth\Contracts\AuthenticationServiceInterface;
use Exception;

final readonly class AuthenticateUserUseCase
{
    public function __construct(private AgentRepositoryInterface $agentRepository, private TokenServiceInterface $tokenService, private AuthenticationDomainServiceInterface $authDomainService, private AuthenticationServiceInterface $authInfrastructureService) {}

    /**
     * Execute authentication workflow
     */
    public function execute(AuthenticateUserCommand $command): AuthenticateUserResponse
    {
        // 1. Validate audience (domain validation)
        $this->authDomainService->validateAudience($command->audience);

        // 2. Find agent by username (infrastructure operation)
        try {
            $username = new Username($command->username);
            $agent = $this->agentRepository->findByUsername($username);
            if (! $agent instanceof \App\Domain\Agent\Models\Agent) {
                throw AuthenticationException::invalidCredentials();
            }
        } catch (Exception) {
            // If username format is invalid, treat as invalid credentials
            throw AuthenticationException::invalidCredentials();
        }

        // 3. Verify password (infrastructure operation)
        if (! $this->agentRepository->verifyPassword($agent, $command->password)) {
            throw AuthenticationException::invalidCredentials();
        }

        // 4. Apply domain business rules
        $this->authDomainService->validateAuthentication($agent, $command->audience);

        // 5. Generate token pair (infrastructure operation)
        $tokenPair = $this->tokenService->generateTokenPair($agent, $command->audience);

        // 6. Store refresh token for blacklisting (infrastructure operation)
        $this->authInfrastructureService->storeRefreshToken($tokenPair->refreshToken());

        // 7. Return successful response
        return AuthenticateUserResponse::success($agent, $tokenPair);
    }
}

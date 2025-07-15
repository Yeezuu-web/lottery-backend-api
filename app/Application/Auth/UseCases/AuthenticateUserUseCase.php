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
use App\Domain\Auth\Services\LoginAuditService;
use App\Domain\Auth\ValueObjects\DeviceInfo;
use App\Infrastructure\Auth\Contracts\AuthenticationServiceInterface;
use Exception;

final readonly class AuthenticateUserUseCase
{
    public function __construct(
        private AgentRepositoryInterface $agentRepository,
        private TokenServiceInterface $tokenService,
        private AuthenticationDomainServiceInterface $authDomainService,
        private AuthenticationServiceInterface $authInfrastructureService,
        private LoginAuditService $loginAuditService
    ) {}

    /**
     * Execute authentication workflow
     */
    public function execute(AuthenticateUserCommand $command): AuthenticateUserResponse
    {
        $loginAudit = null;

        // Record login attempt if request context is available
        if ($command->request !== null) {
            // Check if login should be blocked due to too many failed attempts
            $deviceInfo = DeviceInfo::fromHttpRequest($command->request);
            if ($this->loginAuditService->shouldBlockLogin($command->username, $command->audience, $deviceInfo->ipAddress())) {
                $loginAudit = $this->loginAuditService->recordAttempt($command->username, $command->audience, $command->request);
                $this->loginAuditService->markAsFailed($loginAudit, 'too_many_attempts', $command->username, $command->audience, $deviceInfo);
                throw AuthenticationException::blocked();
            }

            $loginAudit = $this->loginAuditService->recordAttempt($command->username, $command->audience, $command->request);
        }

        try {
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

            // 7. Mark login as successful in audit log
            if ($loginAudit !== null) {
                $this->loginAuditService->markAsSuccessful($loginAudit, $agent, $tokenPair->accessToken());
            }

            // 8. Return successful response
            return AuthenticateUserResponse::success($agent, $tokenPair);

        } catch (AuthenticationException $e) {
            // Mark login as failed in audit log
            if ($loginAudit !== null && $command->request !== null) {
                $deviceInfo = DeviceInfo::fromHttpRequest($command->request);
                $this->loginAuditService->markAsFailed($loginAudit, $e->getMessage(), $command->username, $command->audience, $deviceInfo);
            }
            throw $e;
        }
    }
}

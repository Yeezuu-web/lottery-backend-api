<?php

declare(strict_types=1);

namespace App\Application\Auth\UseCases;

use App\Application\Auth\DTOs\AuthenticateUserCommand;
use App\Application\Auth\DTOs\AuthenticateUserResponse;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Agent\ValueObjects\Username;
use App\Domain\Auth\Contracts\AuthenticationDomainServiceInterface;
use App\Domain\Auth\Contracts\TokenServiceInterface;
use App\Domain\Auth\Events\LoginAttempted;
use App\Domain\Auth\Events\LoginBlocked;
use App\Domain\Auth\Events\LoginFailed;
use App\Domain\Auth\Events\LoginSuccessful;
use App\Domain\Auth\Exceptions\AuthenticationException;
use App\Domain\Auth\Services\LoginAuditService;
use App\Domain\Auth\ValueObjects\DeviceInfo;
use App\Infrastructure\Auth\Contracts\AuthenticationServiceInterface;
use Exception;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Str;

final readonly class AuthenticateUserUseCase
{
    public function __construct(
        private AgentRepositoryInterface $agentRepository,
        private TokenServiceInterface $tokenService,
        private AuthenticationDomainServiceInterface $authDomainService,
        private AuthenticationServiceInterface $authInfrastructureService,
        private LoginAuditService $loginAuditService,
        private EventDispatcher $eventDispatcher
    ) {}

    /**
     * Execute authentication workflow
     */
    public function execute(AuthenticateUserCommand $command): AuthenticateUserResponse
    {
        $deviceInfo = null;

        // Record login attempt if request context is available
        if ($command->request instanceof \Illuminate\Http\Request) {
            $deviceInfo = DeviceInfo::fromHttpRequest($command->request);

            // Check if login should be blocked due to too many failed attempts
            if ($this->loginAuditService->shouldBlockLogin($command->username, $command->audience, $deviceInfo->ipAddress())) {
                $this->eventDispatcher->dispatch(
                    LoginBlocked::now(
                        $command->username,
                        $command->audience,
                        'Too many failed attempts',
                        $deviceInfo,
                        $this->loginAuditService->getFailedAttemptCount($command->username, $command->audience)
                    )
                );
                throw AuthenticationException::blocked();
            }

            // Dispatch login attempted event
            $this->eventDispatcher->dispatch(
                LoginAttempted::now($command->username, $command->audience, $deviceInfo)
            );
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

            // 7. Dispatch login successful event
            if ($deviceInfo instanceof DeviceInfo) {
                $sessionId = 'sess_' . $agent->id() . '_' . $tokenPair->accessToken()->getJti() . '_' . Str::random(8);
                $this->eventDispatcher->dispatch(
                    LoginSuccessful::now($agent, $command->audience, $deviceInfo, $tokenPair->accessToken(), $sessionId)
                );
            }

            // 8. Return successful response
            return AuthenticateUserResponse::success($agent, $tokenPair);

        } catch (AuthenticationException $authenticationException) {
            // Dispatch login failed event
            if ($deviceInfo instanceof DeviceInfo) {
                $this->eventDispatcher->dispatch(
                    LoginFailed::now($command->username, $command->audience, $authenticationException->getMessage(), $deviceInfo)
                );
            }

            throw $authenticationException;
        }
    }
}

<?php

namespace App\Application\Auth\UseCases;

use App\Application\Auth\DTOs\OperationResponse;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Auth\Exceptions\AuthenticationException;
use App\Domain\Auth\Services\AuthenticationDomainService;
use App\Domain\Auth\ValueObjects\JWTToken;

final class GetUserProfileUseCase
{
    private readonly AgentRepositoryInterface $agentRepository;

    private readonly AuthenticationDomainService $authDomainService;

    public function __construct(
        AgentRepositoryInterface $agentRepository,
        AuthenticationDomainService $authDomainService
    ) {
        $this->agentRepository = $agentRepository;
        $this->authDomainService = $authDomainService;
    }

    /**
     * Execute get user profile workflow
     */
    public function execute(JWTToken $token, string $audience): OperationResponse
    {
        // 1. Validate token (domain validation)
        if ($token->isExpired()) {
            throw AuthenticationException::tokenExpired();
        }

        // 2. Find agent by ID from token (infrastructure operation)
        $agent = $this->agentRepository->findById($token->getAgentId());
        if (! $agent) {
            throw AuthenticationException::invalidToken();
        }

        // 3. Apply domain business rules (domain validation)
        $this->authDomainService->validateAuthentication($agent, $audience);

        // 4. Return profile data
        return OperationResponse::success('Profile retrieved successfully', [
            'agent' => [
                'id' => $agent->id(),
                'username' => $agent->username()->value(),
                'email' => $agent->email(),
                'name' => $agent->name(),
                'agent_type' => $agent->agentType()->value(),
                'is_active' => $agent->isActive(),
                'created_at' => $agent->createdAt()?->format('c'),
                'updated_at' => $agent->updatedAt()?->format('c'),
            ],
        ]);
    }
}

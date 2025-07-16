<?php

declare(strict_types=1);

namespace App\Application\Auth\UseCases;

use App\Application\Auth\DTOs\OperationResponse;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Auth\Exceptions\AuthenticationException;
use App\Domain\Auth\Services\AuthenticationDomainService;
use App\Domain\Auth\Services\DatabaseAuthorizationService;
use App\Domain\Auth\ValueObjects\JWTToken;

final readonly class GetUserProfileUseCase
{
    public function __construct(
        private AgentRepositoryInterface $agentRepository,
        private AuthenticationDomainService $authDomainService,
        private DatabaseAuthorizationService $authorizationService
    ) {}

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
        if (! $agent instanceof \App\Domain\Agent\Models\Agent) {
            throw AuthenticationException::invalidToken();
        }

        // 3. Apply domain business rules (domain validation)
        $this->authDomainService->validateAuthentication($agent, $audience);

        // 4. Get permissions from database
        $permissions = $this->authorizationService->getAgentPermissions($agent->id());
        $permissionChain = $this->authorizationService->getPermissionInheritanceChain($agent->id());

        // 5. Return profile data with permissions
        return OperationResponse::success('Profile retrieved successfully', [
            'agent' => [
                'id' => $agent->id(),
                'username' => $agent->username()->value(),
                'email' => $agent->email(),
                'name' => $agent->name(),
                'agent_type' => $agent->agentType()->value(),
                'status' => $agent->status(),
                'is_active' => $agent->isActive(),
                'created_at' => $agent->createdAt()?->format('c'),
                'updated_at' => $agent->updatedAt()?->format('c'),
                'permissions' => $permissions,
                'permission_inheritance' => $permissionChain,
            ],
        ]);
    }
}

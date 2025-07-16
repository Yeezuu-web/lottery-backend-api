<?php

declare(strict_types=1);

namespace App\Traits;

use App\Domain\Agent\Models\Agent;
use App\Domain\Auth\Services\AuthorizationService;
use App\Domain\Auth\ValueObjects\JWTToken;
use Illuminate\Http\Request;
use RuntimeException;

trait HasAuthorization
{
    /**
     * Get current authenticated agent from request
     */
    protected function getCurrentAgent(Request $request): Agent
    {
        $agent = $request->attributes->get('current_agent');

        if (! $agent instanceof Agent) {
            throw new RuntimeException('No authenticated agent found in request');
        }

        return $agent;
    }

    /**
     * Get current JWT token from request
     */
    protected function getCurrentToken(Request $request): JWTToken
    {
        $token = $request->attributes->get('jwt_token');

        if (! $token instanceof JWTToken) {
            throw new RuntimeException('No JWT token found in request');
        }

        return $token;
    }

    /**
     * Check if current agent has permission
     */
    protected function hasPermission(Request $request, string $permission): bool
    {
        $agent = $this->getCurrentAgent($request);
        $token = $this->getCurrentToken($request);

        return $this->getAuthorizationService()->hasPermission($agent, $permission, $token->audience());
    }

    /**
     * Check if current agent can manage target agent
     */
    protected function canManageAgent(Request $request, Agent $targetAgent, string $action): bool
    {
        $currentAgent = $this->getCurrentAgent($request);

        return $this->getAuthorizationService()->canManageAgent($currentAgent, $targetAgent, $action);
    }

    /**
     * Check if current agent can access resource
     */
    protected function canAccessResource(Request $request, string $resource, string $action): bool
    {
        $agent = $this->getCurrentAgent($request);
        $token = $this->getCurrentToken($request);

        return $this->getAuthorizationService()->canAccessResource($agent, $resource, $action, $token->audience());
    }

    /**
     * Authorize current agent for required permissions
     */
    protected function authorize(Request $request, array $permissions): void
    {
        $agent = $this->getCurrentAgent($request);
        $token = $this->getCurrentToken($request);

        $this->getAuthorizationService()->authorize($agent, $permissions, $token->audience());
    }

    /**
     * Get permissions for current agent
     */
    protected function getPermissions(Request $request): array
    {
        $agent = $this->getCurrentAgent($request);
        $token = $this->getCurrentToken($request);

        return $this->getAuthorizationService()->getPermissions($agent, $token->audience());
    }

    /**
     * Get authorization service instance
     */
    private function getAuthorizationService(): AuthorizationService
    {
        return app(AuthorizationService::class);
    }
}

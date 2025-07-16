<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Services\DatabaseAuthorizationService;
use App\Http\Controllers\Controller;
use App\Traits\HttpApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class PermissionController extends Controller
{
    use HttpApiResponse;

    public function __construct(
        private readonly DatabaseAuthorizationService $authService
    ) {}

    /**
     * Get all permissions for an agent
     */
    public function getAgentPermissions(Request $request, int $agentId): JsonResponse
    {
        $currentAgentId = $request->attributes->get('agent_id');

        // Check if current agent can view permissions for target agent
        if (! $this->authService->canManageAgent($currentAgentId, $agentId) && $currentAgentId !== $agentId) {
            return $this->error('Access denied', 403);
        }

        $permissions = $this->authService->getAgentPermissions($agentId);
        $chain = $this->authService->getPermissionInheritanceChain($agentId);

        return $this->success([
            'permissions' => $permissions,
            'inheritance_chain' => $chain,
        ], 'Permissions retrieved successfully');
    }

    /**
     * Grant permission to an agent
     */
    public function grantPermission(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'agent_id' => 'required|integer|exists:agents,id',
            'permission_name' => 'required|string|exists:permissions,name',
            'expires_at' => 'nullable|date|after:now',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $currentAgentId = $request->attributes->get('agent_id');
        $agentId = $request->input('agent_id');
        $permissionName = $request->input('permission_name');
        $expiresAt = $request->input('expires_at') ? now()->parse($request->input('expires_at')) : null;
        $metadata = $request->input('metadata');

        try {
            $agentPermission = $this->authService->grantPermission(
                $agentId,
                $permissionName,
                $currentAgentId,
                $expiresAt,
                $metadata
            );

            return $this->success([
                'permission' => $agentPermission->toArray(),
            ], 'Permission granted successfully');

        } catch (Exception $exception) {
            return $this->error($exception->getMessage(), 400);
        }
    }

    /**
     * Revoke permission from an agent
     */
    public function revokePermission(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'agent_id' => 'required|integer|exists:agents,id',
            'permission_name' => 'required|string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $currentAgentId = $request->attributes->get('agent_id');
        $agentId = $request->input('agent_id');
        $permissionName = $request->input('permission_name');

        try {
            $result = $this->authService->revokePermission(
                $agentId,
                $permissionName,
                $currentAgentId
            );

            if ($result) {
                return $this->success([], 'Permission revoked successfully');
            }

            return $this->error('Permission not found or already revoked', 404);

        } catch (Exception $exception) {
            return $this->error($exception->getMessage(), 400);
        }
    }

    /**
     * Get available permissions for an agent type
     */
    public function getAvailablePermissions(Request $request, string $agentType): JsonResponse
    {
        $currentAgentId = $request->attributes->get('agent_id');

        // Check if current agent can manage permissions
        if (! $this->authService->hasPermission($currentAgentId, 'manage_permissions') &&
            ! $this->authService->hasPermission($currentAgentId, 'grant_permissions')) {
            return $this->error('Access denied', 403);
        }

        $permissions = $this->authService->getAvailablePermissions($agentType);

        return $this->success([
            'permissions' => $permissions->toArray(),
        ], 'Available permissions retrieved successfully');
    }

    /**
     * Bulk grant permissions to multiple agents
     */
    public function bulkGrantPermissions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'agent_ids' => 'required|array|min:1',
            'agent_ids.*' => 'integer|exists:agents,id',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $currentAgentId = $request->attributes->get('agent_id');
        $agentIds = $request->input('agent_ids');
        $permissions = $request->input('permissions');

        try {
            $this->authService->bulkUpdatePermissions($agentIds, $permissions, $currentAgentId);

            return $this->success([], 'Permissions granted successfully to all agents');

        } catch (Exception $exception) {
            return $this->error($exception->getMessage(), 400);
        }
    }

    /**
     * Check if current agent has specific permission
     */
    public function checkPermission(Request $request, string $permission): JsonResponse
    {
        $agentId = $request->attributes->get('agent_id');
        $hasPermission = $this->authService->hasPermission($agentId, $permission);

        return $this->success([
            'has_permission' => $hasPermission,
            'permission' => $permission,
        ], 'Permission check completed');
    }

    /**
     * Get all permissions (for admin interface)
     */
    public function getAllPermissions(Request $request): JsonResponse
    {
        $currentAgentId = $request->attributes->get('agent_id');

        // Only company agents can view all permissions
        if (! $this->authService->hasPermission($currentAgentId, 'manage_permissions')) {
            return $this->error('Access denied', 403);
        }

        $permissions = \App\Domain\Auth\Models\Permission::active()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return $this->success([
            'permissions' => $permissions->toArray(),
        ], 'All permissions retrieved successfully');
    }
}

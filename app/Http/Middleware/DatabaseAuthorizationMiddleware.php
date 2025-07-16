<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Auth\Services\DatabaseAuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class DatabaseAuthorizationMiddleware
{
    public function __construct(
        private DatabaseAuthorizationService $authorizationService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        // Get agent ID from request attributes (set by authentication middleware)
        $agentId = $request->attributes->get('agent_id');

        if (! $agentId) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error' => 'Agent ID not found in request',
            ], 401);
        }

        // Check if agent has any of the required permissions
        if ($permissions !== []) {
            $hasPermission = $this->authorizationService->hasAnyPermission($agentId, $permissions);

            if (! $hasPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied',
                    'error' => 'You do not have permission to access this resource',
                    'required_permissions' => $permissions,
                ], 403);
            }
        }

        return $next($request);
    }
}

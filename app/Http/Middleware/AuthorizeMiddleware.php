<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Auth\Services\AuthorizationService;
use App\Domain\Auth\ValueObjects\JWTToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

final readonly class AuthorizeMiddleware
{
    public function __construct(
        private AuthorizationService $authorizationService,
        private AgentRepositoryInterface $agentRepository
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $permissions  Comma-separated list of required permissions
     * @return Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $permissions)
    {
        // Get JWT token from request
        $token = $request->attributes->get('jwt_token');

        if (! $token instanceof JWTToken) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'timestamp' => now()->toISOString(),
            ], 401);
        }

        // Get agent from token
        $agent = $this->agentRepository->findById($token->getAgentId());

        if (! $agent instanceof \App\Domain\Agent\Models\Agent) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid agent',
                'timestamp' => now()->toISOString(),
            ], 401);
        }

        // Parse required permissions
        $requiredPermissions = array_map('trim', explode(',', $permissions));
        $audience = $token->audience();

        // Check permissions
        try {
            $this->authorizationService->authorize($agent, $requiredPermissions, $audience);
        } catch (Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => $throwable->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 403);
        }

        // Store agent in request for controllers
        $request->attributes->set('current_agent', $agent);

        return $next($request);
    }
}

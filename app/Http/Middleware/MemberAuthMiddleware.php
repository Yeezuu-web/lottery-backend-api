<?php

namespace App\Http\Middleware;

use App\Infrastructure\Auth\Contracts\AuthenticationServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MemberAuthMiddleware
{
    private readonly AuthenticationServiceInterface $authService;

    public function __construct(AuthenticationServiceInterface $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error' => 'No token provided',
            ], 401);
        }

        $jwtToken = $this->authService->verifyToken($token, 'member');

        if (! $jwtToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token',
                'error' => 'Token verification failed',
            ], 401);
        }

        $payload = $jwtToken->payload();

        // Verify agent type is allowed for member endpoints (only 'member' type)
        if ($payload['agent_type'] !== 'member') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
                'error' => 'Only member agents are allowed to access member endpoints',
            ], 403);
        }

        // Store JWT token in request attributes for use in controllers
        $request->attributes->set('jwt_token', $jwtToken);
        $request->attributes->set('agent_id', $payload['agent_id']);
        $request->attributes->set('agent_type', $payload['agent_type']);
        $request->attributes->set('permissions', $payload['permissions']);

        return $next($request);
    }

    /**
     * Extract token from request (Authorization header or cookie)
     */
    private function extractToken(Request $request): ?string
    {
        // Try Authorization header first
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // Try cookie as fallback
        return $request->cookie('member_token');
    }
}

<?php

declare(strict_types=1);

// Application
use App\Http\Middleware\MemberAuthMiddleware;
use App\Http\Middleware\UplineAuthMiddleware;
use Illuminate\Foundation\Application;
// Middleware
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register auth middleware
        $middleware->alias([
            'upline.auth' => UplineAuthMiddleware::class,
            'member.auth' => MemberAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle domain exceptions with appropriate HTTP status codes
        $exceptions->render(function (App\Shared\Exceptions\DomainException $e, $request) {
            // Only handle API requests with JSON responses
            if ($request->expectsJson() || $request->is('api/*')) {
                $message = $e->getMessage();

                // 404 Not Found
                if (str_contains($message, 'not found')) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'timestamp' => now()->toISOString(),
                    ], 404);
                }

                // 403 Forbidden - Permission issues
                if (str_contains($message, 'cannot manage') ||
                    str_contains($message, 'cannot drill down') ||
                    str_contains($message, 'cannot create') ||
                    str_contains($message, 'Access denied')) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'timestamp' => now()->toISOString(),
                    ], 403);
                }

                // 422 Unprocessable Entity - Validation issues
                if (str_contains($message, 'already exists') ||
                    str_contains($message, 'is not valid') ||
                    str_contains($message, 'invalid') ||
                    str_contains($message, 'must have upline') ||
                    str_contains($message, 'cannot have upline')) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'timestamp' => now()->toISOString(),
                    ], 422);
                }

                // 400 Bad Request - Other business logic violations
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'timestamp' => now()->toISOString(),
                ], 400);
            }
        });
    })->create();

<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Auth\UseCases\AuthenticateUserUseCase;
use App\Application\Auth\UseCases\GetUserProfileUseCase;
use App\Application\Auth\UseCases\LogoutUserUseCase;
use App\Application\Auth\UseCases\RefreshTokenUseCase;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Auth\Contracts\TokenServiceInterface;
use App\Domain\Auth\Services\AuthenticationDomainService;
use App\Infrastructure\Agent\Repositories\AgentRepository;
use App\Infrastructure\Auth\Services\AuthenticationService;
use App\Infrastructure\Auth\Services\JWTTokenService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     */
    public function register(): void
    {
        // Register JWT configuration
        $this->app->singleton('jwt.config', fn ($app): array => [
            'upline' => [
                'secret' => env('JWT_UPLINE_SECRET', 'your-upline-secret-key'),
                'issuer' => env('JWT_ISSUER', 'lottery-api'),
                'audience' => 'upline',
                'access_token_ttl' => env('JWT_UPLINE_ACCESS_TTL', 3600), // 1 hour
                'refresh_token_ttl' => env('JWT_UPLINE_REFRESH_TTL', 604800), // 7 days
            ],
            'member' => [
                'secret' => env('JWT_MEMBER_SECRET', 'your-member-secret-key'),
                'issuer' => env('JWT_ISSUER', 'lottery-api'),
                'audience' => 'member',
                'access_token_ttl' => env('JWT_MEMBER_ACCESS_TTL', 1800), // 30 minutes
                'refresh_token_ttl' => env('JWT_MEMBER_REFRESH_TTL', 86400), // 1 day
            ],
        ]);

        // Register domain service
        $this->app->singleton(AuthenticationDomainService::class);

        // Register token service
        $this->app->singleton(TokenServiceInterface::class, JWTTokenService::class);

        // Register repository
        $this->app->singleton(AgentRepositoryInterface::class, AgentRepository::class);

        // Register infrastructure authentication service
        $this->app->singleton(AuthenticationService::class);

        // Register Use Cases
        $this->app->singleton(AuthenticateUserUseCase::class);
        $this->app->singleton(RefreshTokenUseCase::class);
        $this->app->singleton(LogoutUserUseCase::class);
        $this->app->singleton(GetUserProfileUseCase::class);
    }

    /**
     * Bootstrap any authentication / authorization services.
     */
    public function boot(): void
    {
        // Register gates and policies here if needed
        Gate::define('upline-access', fn ($user): bool => in_array($user->agent_type, ['company', 'super_senior', 'senior', 'master', 'agent']));

        Gate::define('member-access', fn ($user): bool => $user->agent_type === 'member');
    }
}

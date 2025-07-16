<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Auth\Contracts\AuthenticationDomainServiceInterface;
use App\Domain\Auth\Services\AuthenticationDomainService;
use App\Infrastructure\Agent\Repositories\AgentRepository;
use App\Infrastructure\Auth\Contracts\AuthenticationServiceInterface;
use App\Infrastructure\Auth\Services\AuthenticationService;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Domain Service Interface
        $this->app->bind(
            AuthenticationDomainServiceInterface::class,
            AuthenticationDomainService::class
        );

        // Bind Infrastructure Service Interface
        $this->app->bind(
            AuthenticationServiceInterface::class,
            AuthenticationService::class
        );

        // Bind Agent Repository Interface
        $this->app->bind(
            AgentRepositoryInterface::class,
            AgentRepository::class
        );

        // Bind Database Authorization Service
        $this->app->singleton(
            \App\Domain\Auth\Services\DatabaseAuthorizationService::class
        );

        // Register Telescope if in local environment
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

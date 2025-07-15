<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Auth\Contracts\LoginAuditRepositoryInterface;
use App\Domain\Auth\Services\LoginAuditService;
use App\Infrastructure\Auth\Models\EloquentLoginAudit;
use App\Infrastructure\Auth\Repositories\LoginAuditRepository;
use Illuminate\Support\ServiceProvider;

final class LoginAuditServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the repository interface binding
        $this->app->singleton(LoginAuditRepositoryInterface::class, function ($app) {
            return new LoginAuditRepository(
                $app->make(EloquentLoginAudit::class)
            );
        });

        // Register the login audit service
        $this->app->singleton(LoginAuditService::class, function ($app) {
            return new LoginAuditService(
                $app->make(LoginAuditRepositoryInterface::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register any boot logic if needed
    }
}

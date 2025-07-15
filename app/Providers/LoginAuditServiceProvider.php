<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Auth\Listeners\LoginAuditListener;
use App\Domain\Auth\Contracts\LoginAuditRepositoryInterface;
use App\Domain\Auth\Events\LoginAttempted;
use App\Domain\Auth\Events\LoginBlocked;
use App\Domain\Auth\Events\LoginFailed;
use App\Domain\Auth\Events\LoginSuccessful;
use App\Domain\Auth\Events\SessionEnded;
use App\Domain\Auth\Events\SuspiciousActivityDetected;
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
        $this->app->singleton(LoginAuditRepositoryInterface::class, fn ($app): LoginAuditRepository => new LoginAuditRepository(
            $app->make(EloquentLoginAudit::class)
        ));

        // Register the login audit service
        $this->app->singleton(LoginAuditService::class, fn ($app): LoginAuditService => new LoginAuditService(
            $app->make(LoginAuditRepositoryInterface::class)
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register event listeners
        $this->app['events']->listen(LoginAttempted::class, [LoginAuditListener::class, 'handleLoginAttempted']);
        $this->app['events']->listen(LoginSuccessful::class, [LoginAuditListener::class, 'handleLoginSuccessful']);
        $this->app['events']->listen(LoginFailed::class, [LoginAuditListener::class, 'handleLoginFailed']);
        $this->app['events']->listen(LoginBlocked::class, [LoginAuditListener::class, 'handleLoginBlocked']);
        $this->app['events']->listen(SessionEnded::class, [LoginAuditListener::class, 'handleSessionEnded']);
        $this->app['events']->listen(SuspiciousActivityDetected::class, [LoginAuditListener::class, 'handleSuspiciousActivityDetected']);
    }
}

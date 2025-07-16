<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\AgentSettings\Contracts\AgentSettingsRepositoryInterface;
use App\Domain\AgentSettings\Contracts\BettingLimitValidationServiceInterface;
use App\Domain\AgentSettings\Services\BettingLimitValidationService;
use App\Infrastructure\AgentSettings\Repositories\AgentSettingsRepository;
use Illuminate\Support\ServiceProvider;

final class AgentSettingsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register repository binding
        $this->app->bind(
            AgentSettingsRepositoryInterface::class,
            AgentSettingsRepository::class
        );

        // Register validation service
        $this->app->singleton(BettingLimitValidationService::class);

        // Bind the interface to the implementation
        $this->app->bind(BettingLimitValidationServiceInterface::class, BettingLimitValidationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

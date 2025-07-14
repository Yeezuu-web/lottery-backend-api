<?php

namespace App\Providers;

use App\Application\AgentSettings\Contracts\AgentSettingsRepositoryInterface;
use App\Infrastructure\AgentSettings\Repositories\AgentSettingsRepository;
use Illuminate\Support\ServiceProvider;

class AgentSettingsServiceProvider extends ServiceProvider
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

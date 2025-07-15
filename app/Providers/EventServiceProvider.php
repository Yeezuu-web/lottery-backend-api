<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Agent\Listeners\CreateWalletsOnAgentCreated;
use App\Domain\Agent\Events\AgentCreated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     */
    protected $listen = [
        // Agent Events
        AgentCreated::class => [
            CreateWalletsOnAgentCreated::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}

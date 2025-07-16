<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Wallet\Contracts\TransactionRepositoryInterface;
// Domain Services
use App\Application\Wallet\Contracts\WalletRepositoryInterface;
use App\Application\Wallet\Services\InterAgentTransferService;
use App\Application\Wallet\UseCases\CreateWalletUseCase;
// Infrastructure Implementations
use App\Application\Wallet\UseCases\CreditWalletUseCase;
use App\Application\Wallet\UseCases\DebitWalletUseCase;
use App\Application\Wallet\UseCases\GetWalletUseCase;
// Application Use Cases
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Infrastructure\Wallet\Repositories\TransactionRepository;
use App\Infrastructure\Wallet\Repositories\WalletRepository;
use App\Infrastructure\Wallet\Services\WalletService;
use Illuminate\Support\ServiceProvider;

final class WalletServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Repository Contracts to Implementations
        $this->app->bind(
            WalletRepositoryInterface::class,
            WalletRepository::class
        );

        $this->app->bind(
            TransactionRepositoryInterface::class,
            TransactionRepository::class
        );

        // Register Use Cases
        $this->app->singleton(CreateWalletUseCase::class, fn ($app): CreateWalletUseCase => new CreateWalletUseCase(
            $app->make(WalletRepositoryInterface::class)
        ));

        $this->app->singleton(CreditWalletUseCase::class, fn ($app): CreditWalletUseCase => new CreditWalletUseCase(
            $app->make(WalletRepositoryInterface::class),
            $app->make(TransactionRepositoryInterface::class)
        ));

        $this->app->singleton(DebitWalletUseCase::class, fn ($app): DebitWalletUseCase => new DebitWalletUseCase(
            $app->make(WalletRepositoryInterface::class),
            $app->make(TransactionRepositoryInterface::class)
        ));

        $this->app->singleton(GetWalletUseCase::class, fn ($app): GetWalletUseCase => new GetWalletUseCase(
            $app->make(WalletRepositoryInterface::class),
            $app->make(TransactionRepositoryInterface::class)
        ));

        // Register Inter-Agent Transfer Service
        $this->app->singleton(InterAgentTransferService::class, fn ($app): InterAgentTransferService => new InterAgentTransferService(
            $app->make(WalletRepositoryInterface::class),
            $app->make(AgentRepositoryInterface::class)
        ));

        // Register Wallet Service
        $this->app->singleton(WalletService::class, fn ($app): WalletService => new WalletService(
            $app->make(WalletRepositoryInterface::class),
            $app->make(TransactionRepositoryInterface::class),
            $app->make(CreateWalletUseCase::class),
            $app->make(CreditWalletUseCase::class),
            $app->make(InterAgentTransferService::class)
        ));

        // Bind the interface to the service
        $this->app->bind(
            \App\Application\Wallet\Contracts\WalletServiceInterface::class,
            WalletService::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Bootstrap any wallet-related services if needed
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            WalletRepositoryInterface::class,
            TransactionRepositoryInterface::class,
            CreateWalletUseCase::class,
            CreditWalletUseCase::class,
            DebitWalletUseCase::class,
            GetWalletUseCase::class,
            WalletService::class,
            \App\Application\Wallet\Contracts\WalletServiceInterface::class,
        ];
    }
}

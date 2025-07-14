<?php

namespace App\Providers;

use App\Application\Wallet\Contracts\TransactionRepositoryInterface;
// Domain Services
use App\Application\Wallet\Contracts\WalletRepositoryInterface;
use App\Application\Wallet\UseCases\CreateWalletUseCase;
// Infrastructure Implementations
use App\Application\Wallet\UseCases\CreditWalletUseCase;
use App\Application\Wallet\UseCases\DebitWalletUseCase;
use App\Application\Wallet\UseCases\GetWalletUseCase;
// Application Use Cases
use App\Infrastructure\Wallet\Repositories\TransactionRepository;
use App\Infrastructure\Wallet\Repositories\WalletRepository;
use App\Infrastructure\Wallet\Services\WalletService;
use Illuminate\Support\ServiceProvider;

class WalletServiceProvider extends ServiceProvider
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
        $this->app->singleton(CreateWalletUseCase::class, function ($app) {
            return new CreateWalletUseCase(
                $app->make(WalletRepositoryInterface::class)
            );
        });

        $this->app->singleton(CreditWalletUseCase::class, function ($app) {
            return new CreditWalletUseCase(
                $app->make(WalletRepositoryInterface::class),
                $app->make(TransactionRepositoryInterface::class)
            );
        });

        $this->app->singleton(DebitWalletUseCase::class, function ($app) {
            return new DebitWalletUseCase(
                $app->make(WalletRepositoryInterface::class),
                $app->make(TransactionRepositoryInterface::class)
            );
        });

        $this->app->singleton(GetWalletUseCase::class, function ($app) {
            return new GetWalletUseCase(
                $app->make(WalletRepositoryInterface::class),
                $app->make(TransactionRepositoryInterface::class)
            );
        });

        // Register Wallet Service
        $this->app->singleton(WalletService::class, function ($app) {
            return new WalletService(
                $app->make(WalletRepositoryInterface::class),
                $app->make(TransactionRepositoryInterface::class),
                $app->make(CreateWalletUseCase::class),
                $app->make(CreditWalletUseCase::class),
                $app->make(GetWalletUseCase::class)
            );
        });
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
        ];
    }
}

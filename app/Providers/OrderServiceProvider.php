<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Order\Contracts\CartRepositoryInterface;
use App\Application\Order\Contracts\ChannelWeightServiceInterface;
use App\Application\Order\Contracts\NumberExpansionServiceInterface;
use App\Application\Order\Contracts\OrderRepositoryInterface;
use App\Application\Order\Contracts\WalletServiceInterface;
use App\Application\Order\UseCases\AddToCartUseCase;
use App\Application\Order\UseCases\SubmitCartUseCase;
use App\Infrastructure\Order\Repositories\CartRepository;
use App\Infrastructure\Order\Repositories\OrderRepository;
use App\Infrastructure\Order\Services\ChannelWeightService;
use App\Infrastructure\Order\Services\NumberExpansionService;
use App\Infrastructure\Order\Services\OrderWalletService;
use Illuminate\Support\ServiceProvider;

final class OrderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Repository Contracts to Implementations
        $this->app->bind(
            CartRepositoryInterface::class,
            CartRepository::class
        );

        $this->app->bind(
            OrderRepositoryInterface::class,
            OrderRepository::class
        );

        // Bind Service Contracts to Implementations
        $this->app->bind(
            NumberExpansionServiceInterface::class,
            NumberExpansionService::class
        );

        $this->app->bind(
            ChannelWeightServiceInterface::class,
            ChannelWeightService::class
        );

        $this->app->bind(
            WalletServiceInterface::class,
            fn($app): \App\Infrastructure\Order\Services\OrderWalletService => new OrderWalletService(
                $app->make(\App\Application\Wallet\Contracts\WalletRepositoryInterface::class),
                $app->make(\App\Application\Wallet\Contracts\TransactionRepositoryInterface::class)
            )
        );

        // Register Use Cases
        $this->app->singleton(AddToCartUseCase::class, fn ($app): AddToCartUseCase => new AddToCartUseCase(
            $app->make(CartRepositoryInterface::class),
            $app->make(\App\Domain\Agent\Contracts\AgentRepositoryInterface::class),
            $app->make(NumberExpansionServiceInterface::class),
            $app->make(ChannelWeightServiceInterface::class),
            $app->make(WalletServiceInterface::class)
        ));

        $this->app->singleton(SubmitCartUseCase::class, fn ($app): SubmitCartUseCase => new SubmitCartUseCase(
            $app->make(CartRepositoryInterface::class),
            $app->make(OrderRepositoryInterface::class),
            $app->make(\App\Domain\Agent\Contracts\AgentRepositoryInterface::class),
            $app->make(WalletServiceInterface::class)
        ));
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Bootstrap any order-related services if needed
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            CartRepositoryInterface::class,
            OrderRepositoryInterface::class,
            NumberExpansionServiceInterface::class,
            ChannelWeightServiceInterface::class,
            WalletServiceInterface::class,
            AddToCartUseCase::class,
            SubmitCartUseCase::class,
        ];
    }
}

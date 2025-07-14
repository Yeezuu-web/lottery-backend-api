<?php

declare(strict_types=1);

namespace App\Application\Wallet\UseCases;

use App\Application\Wallet\Commands\CreateWalletCommand;
use App\Application\Wallet\Contracts\WalletRepositoryInterface;
use App\Application\Wallet\Responses\WalletOperationResponse;
use App\Application\Wallet\Responses\WalletResponse;
use App\Domain\Wallet\Events\WalletCreated;
use App\Domain\Wallet\Exceptions\WalletException;
use App\Domain\Wallet\Models\Wallet;
use Exception;
use Illuminate\Support\Facades\Log;

final readonly class CreateWalletUseCase
{
    public function __construct(
        private WalletRepositoryInterface $walletRepository
    ) {}

    public function execute(CreateWalletCommand $command): WalletOperationResponse
    {
        try {
            // Check if wallet already exists for this owner and type
            if ($this->walletRepository->existsByOwnerAndType($command->ownerId, $command->walletType)) {
                throw WalletException::alreadyExists($command->ownerId, $command->walletType->value);
            }

            // Create new wallet
            $wallet = Wallet::create(
                ownerId: $command->ownerId,
                walletType: $command->walletType,
                currency: $command->currency,
                isActive: $command->isActive
            );

            // Save wallet
            $savedWallet = $this->walletRepository->save($wallet);

            // Log the creation
            Log::info('Wallet created successfully', [
                'wallet_id' => $savedWallet->getId(),
                'owner_id' => $command->ownerId,
                'wallet_type' => $command->walletType->value,
                'currency' => $command->currency,
            ]);

            // Dispatch domain event
            $event = WalletCreated::create($savedWallet);
            // TODO: Dispatch event when event dispatcher is implemented

            return WalletOperationResponse::success(
                message: 'Wallet created successfully',
                data: WalletResponse::fromDomain($savedWallet)
            );
        } catch (WalletException $e) {
            Log::error('Wallet creation failed', [
                'owner_id' => $command->ownerId,
                'wallet_type' => $command->walletType->value,
                'error' => $e->getMessage(),
            ]);

            return WalletOperationResponse::failure(
                message: $e->getMessage(),
                errors: ['wallet' => $e->getMessage()]
            );
        } catch (Exception $e) {
            Log::error('Unexpected error during wallet creation', [
                'owner_id' => $command->ownerId,
                'wallet_type' => $command->walletType->value,
                'error' => $e->getMessage(),
            ]);

            return WalletOperationResponse::failure(
                message: 'Failed to create wallet',
                errors: ['system' => $e->getMessage()]
            );
        }
    }
}

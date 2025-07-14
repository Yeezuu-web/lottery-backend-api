<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

use App\Application\Wallet\Commands\TransferFundsCommand;
use App\Application\Wallet\Responses\WalletOperationResponse;
use App\Domain\Wallet\ValueObjects\WalletType;

interface WalletServiceInterface
{
    /**
     * Initialize wallets for a new agent/user
     */
    public function initializeWalletsForOwner(int $ownerId, string $currency = 'KHR'): WalletOperationResponse;

    /**
     * Transfer funds between wallets
     */
    public function transferFunds(TransferFundsCommand $command): WalletOperationResponse;

    /**
     * Get wallet summary for an owner
     */
    public function getWalletSummary(int $ownerId): WalletOperationResponse;

    /**
     * Get or create wallet for owner and type
     */
    public function getOrCreateWallet(int $ownerId, WalletType $walletType, string $currency = 'KHR'): WalletOperationResponse;
}

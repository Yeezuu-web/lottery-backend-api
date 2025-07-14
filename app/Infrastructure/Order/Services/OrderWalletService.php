<?php

declare(strict_types=1);

namespace App\Infrastructure\Order\Services;

use App\Application\Order\Contracts\WalletServiceInterface;
use App\Application\Wallet\Contracts\WalletRepositoryInterface;
use App\Domain\Agent\Models\Agent;
use App\Domain\Wallet\ValueObjects\Money;
use App\Domain\Wallet\ValueObjects\WalletType;
use Exception;
use Log;

final readonly class OrderWalletService implements WalletServiceInterface
{
    public function __construct(
        private WalletRepositoryInterface $walletRepository
    ) {}

    public function hasEnoughBalance(Agent $agent, Money $amount): bool
    {
        $wallet = $this->walletRepository->findByOwnerIdAndType($agent->id(), WalletType::MAIN);

        if (! $wallet instanceof \App\Domain\Wallet\Models\Wallet) {
            return false;
        }

        return $wallet->getBalance()->amount() >= $amount->amount();
    }

    public function deductBalance(Agent $agent, Money $amount, string $description): bool
    {
        // Use locking to prevent race conditions during wallet operations
        $wallet = $this->walletRepository->findByOwnerIdAndTypeWithLock($agent->id(), WalletType::MAIN);

        if (! $wallet) {
            return false;
        }

        if ($wallet->getBalance()->amount() < $amount->amount()) {
            return false;
        }

        try {
            // Debit the amount from wallet (immutable operation returns new wallet instance)
            $updatedWallet = $wallet->debit($amount);

            // Save the updated wallet with locking to prevent concurrent modifications
            $this->walletRepository->saveWithLock($updatedWallet);

            return true;
        } catch (Exception $exception) {
            // Log the error and return false
            Log::error('Failed to deduct balance from wallet', [
                'agent_id' => $agent->id(),
                'wallet_id' => $wallet->getId(),
                'amount' => $amount->amount(),
                'description' => $description,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function addBalance(Agent $agent, Money $amount, string $description): bool
    {
        // Use locking to prevent race conditions during wallet operations
        $wallet = $this->walletRepository->findByOwnerIdAndTypeWithLock($agent->id(), WalletType::MAIN);

        if (! $wallet) {
            return false;
        }

        try {
            // Credit the amount to wallet (immutable operation returns new wallet instance)
            $updatedWallet = $wallet->credit($amount);

            // Save the updated wallet with locking to prevent concurrent modifications
            $this->walletRepository->saveWithLock($updatedWallet);

            return true;
        } catch (Exception $exception) {
            // Log the error and return false
            Log::error('Failed to add balance to wallet', [
                'agent_id' => $agent->id(),
                'wallet_id' => $wallet->getId(),
                'amount' => $amount->amount(),
                'description' => $description,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function getBalance(Agent $agent): Money
    {
        $wallet = $this->walletRepository->findByOwnerIdAndType($agent->id(), WalletType::MAIN);

        if (! $wallet instanceof \App\Domain\Wallet\Models\Wallet) {
            return Money::zero($agent->getPreferredCurrency());
        }

        return $wallet->getBalance();
    }

    public function getTransactionHistory(Agent $agent, int $limit = 10): array
    {
        // For now, return empty array
        // In a real implementation, this would fetch from transaction repository
        return [];
    }
}

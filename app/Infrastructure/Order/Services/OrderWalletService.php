<?php

declare(strict_types=1);

namespace App\Infrastructure\Order\Services;

use App\Application\Order\Contracts\WalletServiceInterface;
use App\Application\Wallet\Contracts\TransactionRepositoryInterface;
use App\Application\Wallet\Contracts\WalletRepositoryInterface;
use App\Domain\Agent\Models\Agent;
use App\Domain\Wallet\Exceptions\WalletException;
use App\Domain\Wallet\Models\Transaction;
use App\Domain\Wallet\ValueObjects\Money;
use App\Domain\Wallet\ValueObjects\TransactionType;
use App\Domain\Wallet\ValueObjects\WalletType;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class OrderWalletService implements WalletServiceInterface
{
    public function __construct(
        private WalletRepositoryInterface $walletRepository,
        private TransactionRepositoryInterface $transactionRepository
    ) {}

    public function hasEnoughBalance(Agent $agent, Money $amount): bool
    {
        // Use locking for atomic balance check to prevent race conditions
        $wallet = $this->walletRepository->findByOwnerIdAndTypeWithLock($agent->id(), WalletType::MAIN);

        if (! $wallet instanceof \App\Domain\Wallet\Models\Wallet) {
            return false;
        }

        return $wallet->getBalance()->amount() >= $amount->amount();
    }

    public function deductBalance(Agent $agent, Money $amount, string $description): void
    {
        try {
            DB::transaction(function () use ($agent, $amount, $description): void {
                // Find wallet with row-level locking to prevent race conditions
                $wallet = $this->walletRepository->findByOwnerIdAndTypeWithLock($agent->id(), WalletType::MAIN);

                if (!$wallet instanceof \App\Domain\Wallet\Models\Wallet) {
                    throw WalletException::notFound($agent->id());
                }

                // Check if sufficient balance (this is now atomic due to row locking)
                if ($wallet->getBalance()->amount() < $amount->amount()) {
                    throw WalletException::insufficientFunds(
                        $wallet->getId(),
                        $amount,
                        $wallet->getBalance()
                    );
                }

                // Debit the amount from wallet (immutable operation returns new wallet instance)
                $updatedWallet = $wallet->debit($amount);

                // Save the updated wallet
                $this->walletRepository->save($updatedWallet);

                // Create transaction record with secure UUID-based reference
                $transaction = Transaction::create(
                    walletId: $wallet->getId(),
                    type: TransactionType::DEBIT,
                    amount: $amount,
                    balanceAfter: $updatedWallet->getBalance(),
                    reference: 'ORDER_DEBIT_'.Str::uuid().'_'.$agent->id(),
                    description: $description
                );

                $this->transactionRepository->save($transaction);
            });
        } catch (Exception $exception) {
            Log::error('Failed to deduct balance from wallet', [
                'agent_id' => $agent->id(),
                'amount' => $amount->amount(),
                'description' => $description,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function addBalance(Agent $agent, Money $amount, string $description): void
    {
        try {
            DB::transaction(function () use ($agent, $amount, $description): void {
                // Find wallet with row-level locking to prevent race conditions
                $wallet = $this->walletRepository->findByOwnerIdAndTypeWithLock($agent->id(), WalletType::MAIN);

                if (!$wallet instanceof \App\Domain\Wallet\Models\Wallet) {
                    throw WalletException::notFound($agent->id());
                }

                // Credit the amount to wallet (immutable operation returns new wallet instance)
                $updatedWallet = $wallet->credit($amount);

                // Save the updated wallet
                $this->walletRepository->save($updatedWallet);

                // Create transaction record with secure UUID-based reference
                $transaction = Transaction::create(
                    walletId: $wallet->getId(),
                    type: TransactionType::CREDIT,
                    amount: $amount,
                    balanceAfter: $updatedWallet->getBalance(),
                    reference: 'ORDER_CREDIT_'.Str::uuid().'_'.$agent->id(),
                    description: $description
                );

                $this->transactionRepository->save($transaction);
            });
        } catch (Exception $exception) {
            Log::error('Failed to add balance to wallet', [
                'agent_id' => $agent->id(),
                'amount' => $amount->amount(),
                'description' => $description,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function getBalance(Agent $agent): Money
    {
        $wallet = $this->walletRepository->findByOwnerIdAndType($agent->id(), WalletType::MAIN);

        if (! $wallet instanceof \App\Domain\Wallet\Models\Wallet) {
            return Money::zero($this->getDefaultCurrency());
        }

        return $wallet->getBalance();
    }

    public function getTransactionHistory(Agent $agent, int $limit = 10): array
    {
        $wallet = $this->walletRepository->findByOwnerIdAndType($agent->id(), WalletType::MAIN);

        if (! $wallet instanceof \App\Domain\Wallet\Models\Wallet) {
            return [];
        }

        return $this->transactionRepository->findByWallet($wallet->getId(), $limit);
    }

    private function getDefaultCurrency(): string
    {
        return 'KHR'; // Default currency for the lottery system
    }
}

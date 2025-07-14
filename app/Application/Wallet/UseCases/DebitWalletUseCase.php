<?php

namespace App\Application\Wallet\UseCases;

use App\Application\Wallet\Commands\DebitWalletCommand;
use App\Application\Wallet\Contracts\TransactionRepositoryInterface;
use App\Application\Wallet\Contracts\WalletRepositoryInterface;
use App\Application\Wallet\Responses\WalletOperationResponse;
use App\Application\Wallet\Responses\WalletResponse;
use App\Domain\Wallet\Events\TransactionCompleted;
use App\Domain\Wallet\Events\TransactionCreated;
use App\Domain\Wallet\Events\WalletBalanceChanged;
use App\Domain\Wallet\Exceptions\TransactionException;
use App\Domain\Wallet\Exceptions\WalletException;
use App\Domain\Wallet\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class DebitWalletUseCase
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly TransactionRepositoryInterface $transactionRepository
    ) {}

    public function execute(DebitWalletCommand $command): WalletOperationResponse
    {
        try {
            return DB::transaction(function () use ($command) {
                // Find wallet
                $wallet = $this->walletRepository->findById($command->walletId);
                if (! $wallet) {
                    throw WalletException::notFound($command->walletId);
                }

                // Check if reference already exists
                if ($this->transactionRepository->existsByReference($command->reference)) {
                    throw TransactionException::duplicateReference($command->reference);
                }

                // Store original balance for event
                $originalBalance = $wallet->getBalance();

                // Debit wallet
                $debitedWallet = $wallet->debit($command->amount);

                // Create transaction record
                $transaction = Transaction::create(
                    walletId: $command->walletId,
                    type: $command->transactionType,
                    amount: $command->amount,
                    balanceAfter: $debitedWallet->getBalance(),
                    reference: $command->reference,
                    description: $command->description,
                    metadata: $command->metadata,
                    relatedTransactionId: $command->relatedTransactionId,
                    orderId: $command->orderId
                );

                // Save transaction
                $savedTransaction = $this->transactionRepository->save($transaction);

                // Complete transaction
                $completedTransaction = $savedTransaction->complete();
                $this->transactionRepository->save($completedTransaction);

                // Save updated wallet
                $savedWallet = $this->walletRepository->save($debitedWallet);

                // Log the debit
                Log::info('Wallet debited successfully', [
                    'wallet_id' => $command->walletId,
                    'amount' => $command->amount->toArray(),
                    'transaction_id' => $completedTransaction->getId(),
                    'reference' => $command->reference,
                ]);

                // Dispatch domain events
                $walletBalanceEvent = WalletBalanceChanged::create(
                    wallet: $savedWallet,
                    previousBalance: $originalBalance,
                    reason: 'debit',
                    transactionId: $completedTransaction->getId()
                );

                $transactionCreatedEvent = TransactionCreated::create($completedTransaction);
                $transactionCompletedEvent = TransactionCompleted::create($completedTransaction);

                // TODO: Dispatch events when event dispatcher is implemented

                return WalletOperationResponse::success(
                    message: 'Wallet debited successfully',
                    data: [
                        'wallet' => WalletResponse::fromDomain($savedWallet),
                        'transaction' => $completedTransaction->toArray(),
                    ]
                );
            });
        } catch (WalletException|TransactionException $e) {
            Log::error('Wallet debit failed', [
                'wallet_id' => $command->walletId,
                'amount' => $command->amount->toArray(),
                'error' => $e->getMessage(),
            ]);

            return WalletOperationResponse::failure(
                message: $e->getMessage(),
                errors: ['wallet' => $e->getMessage()]
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error during wallet debit', [
                'wallet_id' => $command->walletId,
                'amount' => $command->amount->toArray(),
                'error' => $e->getMessage(),
            ]);

            return WalletOperationResponse::failure(
                message: 'Failed to debit wallet',
                errors: ['system' => $e->getMessage()]
            );
        }
    }
}

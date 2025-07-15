<?php

declare(strict_types=1);

namespace App\Application\Wallet\UseCases;

use App\Application\Wallet\Commands\CreditWalletCommand;
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
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class CreditWalletUseCase
{
    public function __construct(
        private WalletRepositoryInterface $walletRepository,
        private TransactionRepositoryInterface $transactionRepository
    ) {}

    public function execute(CreditWalletCommand $command): WalletOperationResponse
    {
        try {
            return DB::transaction(function () use ($command): WalletOperationResponse {
                // Find wallet
                $wallet = $this->walletRepository->findById($command->walletId);
                if (! $wallet instanceof \App\Domain\Wallet\Models\Wallet) {
                    throw WalletException::notFound($command->walletId);
                }

                // Generate reference if not provided
                $reference = $command->reference ?? $this->generateUniqueReference('CR');

                // Check if reference already exists
                if ($this->transactionRepository->existsByReference($reference)) {
                    throw TransactionException::duplicateReference($reference);
                }

                // Store original balance for event
                $originalBalance = $wallet->getBalance();

                // Credit wallet
                $creditedWallet = $wallet->credit($command->amount);

                // Create transaction record
                $transaction = Transaction::create(
                    walletId: $command->walletId,
                    type: $command->transactionType,
                    amount: $command->amount,
                    balanceAfter: $creditedWallet->getBalance(),
                    reference: $reference,
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
                $savedWallet = $this->walletRepository->save($creditedWallet);

                // Log the credit
                Log::info('Wallet credited successfully', [
                    'wallet_id' => $command->walletId,
                    'amount' => $command->amount->toArray(),
                    'transaction_id' => $completedTransaction->getId(),
                    'reference' => $command->reference,
                ]);

                // Dispatch domain events
                $walletBalanceEvent = WalletBalanceChanged::create(
                    wallet: $savedWallet,
                    previousBalance: $originalBalance,
                    reason: 'credit',
                    transactionId: $completedTransaction->getId()
                );

                $transactionCreatedEvent = TransactionCreated::create($completedTransaction);
                $transactionCompletedEvent = TransactionCompleted::create($completedTransaction);

                // TODO: Dispatch events when event dispatcher is implemented

                return WalletOperationResponse::success(
                    message: 'Wallet credited successfully',
                    data: [
                        'wallet' => WalletResponse::fromDomain($savedWallet),
                        'transaction' => $completedTransaction->toArray(),
                    ]
                );
            });
        } catch (WalletException|TransactionException $e) {
            Log::error('Wallet credit failed', [
                'wallet_id' => $command->walletId,
                'amount' => $command->amount->toArray(),
                'error' => $e->getMessage(),
            ]);

            return WalletOperationResponse::failure(
                message: $e->getMessage(),
                errors: ['wallet' => $e->getMessage()]
            );
        } catch (Exception $e) {
            Log::error('Unexpected error during wallet credit', [
                'wallet_id' => $command->walletId,
                'amount' => $command->amount->toArray(),
                'error' => $e->getMessage(),
            ]);

            return WalletOperationResponse::failure(
                message: 'Failed to credit wallet',
                errors: ['system' => $e->getMessage()]
            );
        }
    }

    private function generateUniqueReference(string $prefix): string
    {
        return $prefix.'_'.str_replace('.', '', (string) microtime(true)).'_'.str_replace('-', '', (string) \Illuminate\Support\Str::uuid());
    }


}

<?php

namespace App\Application\Wallet\UseCases;

use App\Application\Wallet\Contracts\TransactionRepositoryInterface;
use App\Application\Wallet\Contracts\WalletRepositoryInterface;
use App\Application\Wallet\Queries\GetWalletQuery;
use App\Application\Wallet\Responses\TransactionResponse;
use App\Application\Wallet\Responses\WalletOperationResponse;
use App\Application\Wallet\Responses\WalletResponse;
use App\Domain\Wallet\Exceptions\WalletException;
use Illuminate\Support\Facades\Log;

final class GetWalletUseCase
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly TransactionRepositoryInterface $transactionRepository
    ) {}

    public function execute(GetWalletQuery $query): WalletOperationResponse
    {
        try {
            // Find wallet
            $wallet = $this->walletRepository->findById($query->walletId);
            if (! $wallet) {
                throw WalletException::notFound($query->walletId);
            }

            // Get transactions if requested
            $transactions = null;
            if ($query->includeTransactions) {
                $transactionModels = $this->transactionRepository->findByWallet(
                    walletId: $query->walletId,
                    limit: $query->transactionLimit
                );

                $transactions = array_map(
                    fn ($transaction) => TransactionResponse::fromDomain($transaction)->toArray(),
                    $transactionModels
                );
            }

            // Log the retrieval
            Log::info('Wallet retrieved successfully', [
                'wallet_id' => $query->walletId,
                'include_transactions' => $query->includeTransactions,
                'transaction_count' => $transactions ? count($transactions) : 0,
            ]);

            return WalletOperationResponse::success(
                message: 'Wallet retrieved successfully',
                data: WalletResponse::fromDomain($wallet, $transactions)
            );
        } catch (WalletException $e) {
            Log::error('Wallet retrieval failed', [
                'wallet_id' => $query->walletId,
                'error' => $e->getMessage(),
            ]);

            return WalletOperationResponse::failure(
                message: $e->getMessage(),
                errors: ['wallet' => $e->getMessage()]
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error during wallet retrieval', [
                'wallet_id' => $query->walletId,
                'error' => $e->getMessage(),
            ]);

            return WalletOperationResponse::failure(
                message: 'Failed to retrieve wallet',
                errors: ['system' => $e->getMessage()]
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wallet;

use App\Application\Wallet\Commands\TransferFundsCommand;
use App\Application\Wallet\Contracts\TransactionRepositoryInterface;
use App\Application\Wallet\Queries\GetTransactionHistoryQuery;
use App\Application\Wallet\Responses\TransactionResponse;
use App\Domain\Wallet\ValueObjects\TransactionStatus;
use App\Domain\Wallet\ValueObjects\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\TransferFundsRequest;
use App\Infrastructure\Wallet\Services\WalletService;
use App\Traits\HttpApiResponse;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TransactionController extends Controller
{
    use HttpApiResponse;

    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly WalletService $walletService
    ) {}

    /**
     * Get transaction by ID
     */
    public function show(int $transactionId): JsonResponse
    {
        try {
            $transaction = $this->transactionRepository->findById($transactionId);

            if (! $transaction instanceof \App\Domain\Wallet\Models\Transaction) {
                return $this->error(
                    message: 'Transaction not found',
                    code: 404
                );
            }

            return $this->success(
                data: TransactionResponse::fromDomain($transaction)->toArray(),
                message: 'Transaction retrieved successfully'
            );
        } catch (Exception $exception) {
            return $this->error(
                message: 'Failed to retrieve transaction',
                code: 500,
                errors: ['system' => $exception->getMessage()]
            );
        }
    }

    /**
     * Get transaction history for a wallet
     */
    public function getHistory(int $walletId, Request $request): JsonResponse
    {
        try {
            $transactionType = $request->get('type');
            $status = $request->get('status');
            $fromDate = $request->get('from_date');
            $toDate = $request->get('to_date');
            $page = $request->integer('page', 1);
            $perPage = $request->integer('per_page', 20);
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            $query = new GetTransactionHistoryQuery(
                walletId: $walletId,
                transactionType: $transactionType ? TransactionType::from($transactionType) : null,
                status: $status ? TransactionStatus::from($status) : null,
                fromDate: $fromDate ? Carbon::parse($fromDate) : null,
                toDate: $toDate ? Carbon::parse($toDate) : null,
                page: $page,
                perPage: $perPage,
                sortBy: $sortBy,
                sortDirection: $sortDirection
            );

            // For now, use the repository directly
            // In a complete implementation, you'd create a use case for this
            $transactions = $this->transactionRepository->findByWallet(
                walletId: $walletId,
                limit: $perPage,
                offset: ($page - 1) * $perPage
            );

            // Apply filters if provided
            if ($transactionType) {
                $transactions = array_filter($transactions, fn ($t): bool => $t->getType()->value === $transactionType);
            }

            if ($status) {
                $transactions = array_filter($transactions, fn ($t): bool => $t->getStatus()->value === $status);
            }

            if ($fromDate && $toDate) {
                $transactions = array_filter($transactions, fn ($t) => $t->getCreatedAt()->between($fromDate, $toDate));
            }

            $transactionResponses = array_map(
                fn ($transaction): array => TransactionResponse::fromDomain($transaction)->toArray(),
                array_values($transactions)
            );

            $totalCount = $this->transactionRepository->countByWallet($walletId);

            return $this->success(
                data: [
                    'transactions' => $transactionResponses,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $totalCount,
                        'last_page' => ceil($totalCount / $perPage),
                    ],
                ],
                message: 'Transaction history retrieved successfully'
            );
        } catch (Exception $exception) {
            return $this->error(
                message: 'Failed to retrieve transaction history',
                code: 500,
                errors: ['system' => $exception->getMessage()]
            );
        }
    }

    /**
     * Transfer funds between wallets
     */
    public function transfer(TransferFundsRequest $request): JsonResponse
    {
        $command = new TransferFundsCommand(
            fromWalletId: $request->getFromWalletId(),
            toWalletId: $request->getToWalletId(),
            amount: $request->getAmount(),
            reference: $request->getReference(),
            description: $request->getDescription(),
            metadata: $request->getMetadata(),
            orderId: $request->getOrderId()
        );

        $result = $this->walletService->transferFunds($command);

        if (! $result->success) {
            return $this->error(
                message: $result->message,
                code: $this->getStatusCodeFromMessage($result->message),
                errors: $result->errors
            );
        }

        return $this->success(
            data: $result->data,
            message: $result->message
        );
    }

    /**
     * Get transaction summary for a wallet
     */
    public function getSummary(int $walletId, Request $request): JsonResponse
    {
        try {
            $fromDate = $request->get('from_date')
                ? Carbon::parse($request->get('from_date'))
                : Carbon::now()->startOfMonth();

            $toDate = $request->get('to_date')
                ? Carbon::parse($request->get('to_date'))
                : Carbon::now()->endOfMonth();

            $summary = $this->transactionRepository->getTransactionSummary(
                walletId: $walletId,
                fromDate: $fromDate,
                toDate: $toDate
            );

            $totalAmounts = $this->transactionRepository->getTotalAmountByWallet($walletId);

            return $this->success(
                data: [
                    'wallet_id' => $walletId,
                    'period' => [
                        'from' => $fromDate->toISOString(),
                        'to' => $toDate->toISOString(),
                    ],
                    'summary_by_type' => $summary,
                    'totals' => $totalAmounts,
                ],
                message: 'Transaction summary retrieved successfully'
            );
        } catch (Exception $exception) {
            return $this->error(
                message: 'Failed to retrieve transaction summary',
                code: 500,
                errors: ['system' => $exception->getMessage()]
            );
        }
    }

    /**
     * Get transaction by reference
     */
    public function getByReference(string $reference): JsonResponse
    {
        try {
            $transaction = $this->transactionRepository->findByReference($reference);

            if (! $transaction instanceof \App\Domain\Wallet\Models\Transaction) {
                return $this->error(
                    message: 'Transaction not found',
                    code: 404
                );
            }

            return $this->success(
                data: TransactionResponse::fromDomain($transaction)->toArray(),
                message: 'Transaction retrieved successfully'
            );
        } catch (Exception $exception) {
            return $this->error(
                message: 'Failed to retrieve transaction',
                code: 500,
                errors: ['system' => $exception->getMessage()]
            );
        }
    }

    /**
     * Get latest transactions for a wallet
     */
    public function getLatest(int $walletId, Request $request): JsonResponse
    {
        try {
            $limit = $request->integer('limit', 10);

            $transactions = $this->transactionRepository->findLatestByWallet(
                walletId: $walletId,
                limit: $limit
            );

            $transactionResponses = array_map(
                fn ($transaction): array => TransactionResponse::fromDomain($transaction)->toArray(),
                $transactions
            );

            return $this->success(
                data: [
                    'wallet_id' => $walletId,
                    'transactions' => $transactionResponses,
                    'count' => count($transactionResponses),
                ],
                message: 'Latest transactions retrieved successfully'
            );
        } catch (Exception $exception) {
            return $this->error(
                message: 'Failed to retrieve latest transactions',
                code: 500,
                errors: ['system' => $exception->getMessage()]
            );
        }
    }

    /**
     * Get transaction statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $statistics = $this->transactionRepository->getTransactionStatistics();

            return $this->success(
                data: [
                    'statistics' => $statistics,
                    'generated_at' => Carbon::now()->toISOString(),
                ],
                message: 'Transaction statistics retrieved successfully'
            );
        } catch (Exception $exception) {
            return $this->error(
                message: 'Failed to retrieve transaction statistics',
                code: 500,
                errors: ['system' => $exception->getMessage()]
            );
        }
    }

    /**
     * Get appropriate HTTP status code based on error message
     */
    private function getStatusCodeFromMessage(string $message): int
    {
        if (str_contains($message, 'not found')) {
            return 404;
        }

        if (str_contains($message, 'already exist')) {
            return 409;
        }

        if (str_contains($message, 'insufficient') || str_contains($message, 'invalid')) {
            return 422;
        }

        if (str_contains($message, 'permission') || str_contains($message, 'unauthorized')) {
            return 403;
        }

        return 400;
    }
}

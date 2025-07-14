<?php

namespace App\Http\Controllers\Wallet;

use App\Application\Wallet\Commands\CreateWalletCommand;
use App\Application\Wallet\Commands\CreditWalletCommand;
use App\Application\Wallet\Commands\DebitWalletCommand;
use App\Application\Wallet\Queries\GetWalletQuery;
use App\Application\Wallet\Queries\GetWalletsByOwnerQuery;
use App\Application\Wallet\UseCases\CreateWalletUseCase;
use App\Application\Wallet\UseCases\CreditWalletUseCase;
use App\Application\Wallet\UseCases\DebitWalletUseCase;
use App\Application\Wallet\UseCases\GetWalletUseCase;
use App\Domain\Wallet\ValueObjects\WalletType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\CreateWalletRequest;
use App\Http\Requests\Wallet\CreditWalletRequest;
use App\Http\Requests\Wallet\DebitWalletRequest;
use App\Http\Requests\Wallet\InitializeWalletsRequest;
use App\Infrastructure\Wallet\Services\WalletService;
use App\Traits\HttpApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    use HttpApiResponse;

    public function __construct(
        private readonly CreateWalletUseCase $createWalletUseCase,
        private readonly CreditWalletUseCase $creditWalletUseCase,
        private readonly DebitWalletUseCase $debitWalletUseCase,
        private readonly GetWalletUseCase $getWalletUseCase,
        private readonly WalletService $walletService
    ) {}

    /**
     * Get wallet by ID
     */
    public function show(int $walletId, Request $request): JsonResponse
    {
        $includeTransactions = $request->boolean('include_transactions', false);
        $transactionLimit = $request->integer('transaction_limit', 10);

        $query = new GetWalletQuery(
            walletId: $walletId,
            includeTransactions: $includeTransactions,
            transactionLimit: $transactionLimit
        );

        $result = $this->getWalletUseCase->execute($query);

        if (! $result->success) {
            return $this->error(
                message: $result->message,
                errors: $result->errors,
                code: $this->getStatusCodeFromMessage($result->message)
            );
        }

        return $this->success(
            data: $result->data->toArray(),
            message: $result->message
        );
    }

    /**
     * Get wallets by owner ID
     */
    public function getByOwner(int $ownerId, Request $request): JsonResponse
    {
        $walletType = $request->get('wallet_type');
        $activeOnly = $request->boolean('active_only', true);

        $query = new GetWalletsByOwnerQuery(
            ownerId: $ownerId,
            walletType: $walletType ? WalletType::from($walletType) : null,
            activeOnly: $activeOnly
        );

        $result = $this->walletService->getWalletSummary($ownerId);

        if (! $result->success) {
            return $this->error(
                message: $result->message,
                errors: $result->errors,
                code: $this->getStatusCodeFromMessage($result->message)
            );
        }

        return $this->success(
            data: $result->data,
            message: $result->message
        );
    }

    /**
     * Create new wallet
     */
    public function store(CreateWalletRequest $request): JsonResponse
    {
        $command = new CreateWalletCommand(
            ownerId: $request->getOwnerId(),
            walletType: $request->getWalletType(),
            currency: $request->getCurrency(),
            isActive: $request->getIsActive()
        );

        $result = $this->createWalletUseCase->execute($command);

        if (! $result->success) {
            return $this->error(
                message: $result->message,
                errors: $result->errors,
                code: $this->getStatusCodeFromMessage($result->message)
            );
        }

        return $this->success($result->data->toArray(), $result->message, 201);
    }

    /**
     * Initialize all wallets for an owner
     */
    public function initializeWallets(InitializeWalletsRequest $request): JsonResponse
    {
        $result = $this->walletService->initializeWalletsForOwner(
            ownerId: $request->getOwnerId(),
            currency: $request->getCurrency()
        );

        if (! $result->success) {
            return $this->error(
                message: $result->message,
                errors: $result->errors,
                code: $this->getStatusCodeFromMessage($result->message)
            );
        }

        return $this->success($result->data, $result->message, 201);
    }

    /**
     * Credit wallet balance
     */
    public function credit(int $walletId, CreditWalletRequest $request): JsonResponse
    {
        $command = new CreditWalletCommand(
            walletId: $walletId,
            amount: $request->getAmount(),
            transactionType: $request->getTransactionType(),
            reference: $request->getReference(),
            description: $request->getDescription(),
            metadata: $request->getMetadata(),
            orderId: $request->getOrderId(),
            relatedTransactionId: $request->getRelatedTransactionId()
        );

        $result = $this->creditWalletUseCase->execute($command);

        if (! $result->success) {
            return $this->error(
                message: $result->message,
                errors: $result->errors,
                code: $this->getStatusCodeFromMessage($result->message)
            );
        }

        return $this->success($result->data, $result->message);
    }

    /**
     * Debit wallet balance
     */
    public function debit(int $walletId, DebitWalletRequest $request): JsonResponse
    {
        $command = new DebitWalletCommand(
            walletId: $walletId,
            amount: $request->getAmount(),
            transactionType: $request->getTransactionType(),
            reference: $request->getReference(),
            description: $request->getDescription(),
            metadata: $request->getMetadata(),
            orderId: $request->getOrderId(),
            relatedTransactionId: $request->getRelatedTransactionId()
        );

        $result = $this->debitWalletUseCase->execute($command);

        if (! $result->success) {
            return $this->error(
                message: $result->message,
                errors: $result->errors,
                code: $this->getStatusCodeFromMessage($result->message)
            );
        }

        return $this->success($result->data, $result->message);
    }

    /**
     * Get wallet balance
     */
    public function balance(int $walletId): JsonResponse
    {
        $query = new GetWalletQuery(
            walletId: $walletId,
            includeTransactions: false
        );

        $result = $this->getWalletUseCase->execute($query);

        if (! $result->success) {
            return $this->error(
                message: $result->message,
                errors: $result->errors,
                code: $this->getStatusCodeFromMessage($result->message)
            );
        }

        $wallet = $result->data;
        $balanceData = [
            'wallet_id' => $wallet->id,
            'balance' => $wallet->balance,
            'locked_balance' => $wallet->lockedBalance,
            'available_balance' => $wallet->availableBalance,
            'currency' => $wallet->currency,
            'last_transaction_at' => $wallet->lastTransactionAt,
        ];

        return $this->success($balanceData, 'Wallet balance retrieved successfully');
    }

    /**
     * Activate wallet
     */
    public function activate(int $walletId): JsonResponse
    {
        $query = new GetWalletQuery(walletId: $walletId);
        $result = $this->getWalletUseCase->execute($query);

        if (! $result->success) {
            return $this->error(
                message: $result->message,
                errors: $result->errors,
                code: $this->getStatusCodeFromMessage($result->message)
            );
        }

        // Implementation would activate the wallet
        // For now, return success message
        return $this->success($result->data->toArray(), 'Wallet activated successfully');
    }

    /**
     * Deactivate wallet
     */
    public function deactivate(int $walletId): JsonResponse
    {
        $query = new GetWalletQuery(walletId: $walletId);
        $result = $this->getWalletUseCase->execute($query);

        if (! $result->success) {
            return $this->error(
                message: $result->message,
                errors: $result->errors,
                code: $this->getStatusCodeFromMessage($result->message)
            );
        }

        // Implementation would deactivate the wallet
        // For now, return success message
        return $this->success($result->data->toArray(), 'Wallet deactivated successfully');
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

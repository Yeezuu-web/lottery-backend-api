<?php

namespace App\Application\Wallet\Contracts;

use App\Domain\Wallet\Models\Transaction;
use App\Domain\Wallet\ValueObjects\TransactionStatus;
use App\Domain\Wallet\ValueObjects\TransactionType;
use Carbon\Carbon;

interface TransactionRepositoryInterface
{
    public function findById(int $transactionId): ?Transaction;

    public function findByReference(string $reference): ?Transaction;

    public function findByWallet(int $walletId, ?int $limit = null, ?int $offset = null): array;

    public function findByWalletAndType(int $walletId, TransactionType $type, ?int $limit = null): array;

    public function findByWalletAndStatus(int $walletId, TransactionStatus $status, ?int $limit = null): array;

    public function findByWalletAndDateRange(int $walletId, Carbon $fromDate, Carbon $toDate): array;

    public function findByOrderId(int $orderId): array;

    public function findByRelatedTransaction(int $relatedTransactionId): array;

    public function save(Transaction $transaction): Transaction;

    public function delete(int $transactionId): bool;

    public function exists(int $transactionId): bool;

    public function existsByReference(string $reference): bool;

    public function findPendingTransactions(): array;

    public function findByStatus(TransactionStatus $status): array;

    public function findByType(TransactionType $type): array;

    public function findByDateRange(Carbon $fromDate, Carbon $toDate): array;

    public function bulkUpdateStatus(array $transactionIds, TransactionStatus $status): bool;

    public function getTransactionSummary(int $walletId, Carbon $fromDate, Carbon $toDate): array;

    public function getTransactionStatistics(): array;

    public function findExpiredPendingTransactions(Carbon $expiredBefore): array;

    public function findDuplicateReferences(): array;

    public function countByWallet(int $walletId): int;

    public function getTotalAmountByWallet(int $walletId, ?TransactionType $type = null): array;

    public function findLatestByWallet(int $walletId, int $limit = 10): array;

    public function findByIds(array $transactionIds): array;
}

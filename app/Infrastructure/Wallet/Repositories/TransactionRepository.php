<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet\Repositories;

use App\Application\Wallet\Contracts\TransactionRepositoryInterface;
use App\Domain\Wallet\Models\Transaction;
use App\Domain\Wallet\ValueObjects\Money;
use App\Domain\Wallet\ValueObjects\TransactionStatus;
use App\Domain\Wallet\ValueObjects\TransactionType;
use App\Infrastructure\Wallet\Models\EloquentTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

final readonly class TransactionRepository implements TransactionRepositoryInterface
{
    private const CACHE_PREFIX = 'transaction';

    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private EloquentTransaction $model
    ) {}

    public function findById(int $transactionId): ?Transaction
    {
        $cacheKey = $this->getCacheKey($transactionId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($transactionId): ?\App\Domain\Wallet\Models\Transaction {
            $eloquentTransaction = $this->model
                ->with(['wallet.owner', 'order', 'relatedTransaction'])
                ->find($transactionId);

            return $eloquentTransaction ? $this->mapFromEloquent($eloquentTransaction) : null;
        });
    }

    public function findByReference(string $reference): ?Transaction
    {
        $cacheKey = $this->getReferenceCacheKey($reference);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($reference): ?\App\Domain\Wallet\Models\Transaction {
            $eloquentTransaction = $this->model
                ->with(['wallet.owner', 'order', 'relatedTransaction'])
                ->byReference($reference)
                ->first();

            return $eloquentTransaction ? $this->mapFromEloquent($eloquentTransaction) : null;
        });
    }

    public function findByWallet(int $walletId, ?int $limit = null, ?int $offset = null): array
    {
        $query = $this->model
            ->with(['wallet.owner', 'order', 'relatedTransaction'])
            ->where('wallet_id', $walletId)
            ->orderBy('created_at', 'desc');

        if ($limit !== null) {
            $query->limit($limit);
        }

        if ($offset !== null) {
            $query->offset($offset);
        }

        $eloquentTransactions = $query->get();

        return $eloquentTransactions->map(fn ($transaction): Transaction => $this->mapFromEloquent($transaction))->toArray();
    }

    public function findByWalletAndType(int $walletId, TransactionType $transactionType, ?int $limit = null): array
    {
        $query = $this->model
            ->with(['wallet.owner', 'order', 'relatedTransaction'])
            ->where('wallet_id', $walletId)
            ->ofType($transactionType->value)
            ->orderBy('created_at', 'desc');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $eloquentTransactions = $query->get();

        return $eloquentTransactions->map(fn ($transaction): Transaction => $this->mapFromEloquent($transaction))->toArray();
    }

    public function findByWalletAndStatus(int $walletId, TransactionStatus $status, ?int $limit = null): array
    {
        $query = $this->model
            ->with(['wallet.owner', 'order', 'relatedTransaction'])
            ->where('wallet_id', $walletId)
            ->ofStatus($status->value)
            ->orderBy('created_at', 'desc');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $eloquentTransactions = $query->get();

        return $eloquentTransactions->map(fn ($transaction): Transaction => $this->mapFromEloquent($transaction))->toArray();
    }

    public function findByWalletAndDateRange(int $walletId, Carbon $fromDate, Carbon $toDate): array
    {
        $eloquentTransactions = $this->model
            ->with(['wallet.owner', 'order', 'relatedTransaction'])
            ->where('wallet_id', $walletId)
            ->whereBetween('created_at', [$fromDate->startOfDay(), $toDate->endOfDay()])
            ->orderBy('created_at', 'desc')
            ->get();

        return $eloquentTransactions->map(fn ($transaction): Transaction => $this->mapFromEloquent($transaction))->toArray();
    }

    public function findByOrderId(int $orderId): array
    {
        $eloquentTransactions = $this->model
            ->with(['wallet.owner', 'order', 'relatedTransaction'])
            ->where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $eloquentTransactions->map(fn ($transaction): Transaction => $this->mapFromEloquent($transaction))->toArray();
    }

    public function findByRelatedTransaction(int $relatedTransactionId): array
    {
        $eloquentTransactions = $this->model
            ->with(['wallet.owner', 'order', 'relatedTransaction'])
            ->where('related_transaction_id', $relatedTransactionId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $eloquentTransactions->map(fn ($transaction): Transaction => $this->mapFromEloquent($transaction))->toArray();
    }

    public function save(Transaction $transaction): Transaction
    {
        $eloquentTransaction = $this->model->find($transaction->getId());

        if (! $eloquentTransaction) {
            $eloquentTransaction = new EloquentTransaction;
        }

        $eloquentTransaction->fill([
            'wallet_id' => $transaction->getWalletId(),
            'type' => $transaction->getType()->value,
            'amount' => $transaction->getAmount()->amount(),
            'balance_after' => $transaction->getBalanceAfter()->amount(),
            'reference' => $transaction->getReference(),
            'description' => $transaction->getDescription(),
            'status' => $transaction->getStatus()->value,
            'metadata' => $transaction->getMetadata(),
            'related_transaction_id' => $transaction->getRelatedTransactionId(),
            'order_id' => $transaction->getOrderId(),
        ]);

        $eloquentTransaction->save();

        // Clear related caches
        $this->clearCache($transaction);

        return $this->mapFromEloquent($eloquentTransaction);
    }

    public function delete(int $transactionId): bool
    {
        $transaction = $this->findById($transactionId);
        if (! $transaction instanceof Transaction) {
            return false;
        }

        $deleted = $this->model->where('id', $transactionId)->delete() > 0;

        if ($deleted) {
            $this->clearCache($transaction);
        }

        return $deleted;
    }

    public function exists(int $transactionId): bool
    {
        return $this->model->where('id', $transactionId)->exists();
    }

    public function existsByReference(string $reference): bool
    {
        return $this->model->byReference($reference)->exists();
    }

    public function findPendingTransactions(): array
    {
        $eloquentTransactions = $this->model
            ->with(['wallet.owner', 'order', 'relatedTransaction'])
            ->pending()
            ->orderBy('created_at', 'asc')
            ->get();

        return $eloquentTransactions->map(fn ($transaction): Transaction => $this->mapFromEloquent($transaction))->toArray();
    }

    public function findByStatus(TransactionStatus $status): array
    {
        $eloquentTransactions = $this->model
            ->with(['wallet.owner', 'order', 'relatedTransaction'])
            ->ofStatus($status->value)
            ->orderBy('created_at', 'desc')
            ->get();

        return $eloquentTransactions->map(fn ($transaction): Transaction => $this->mapFromEloquent($transaction))->toArray();
    }

    public function findByType(TransactionType $type): array
    {
        $eloquentTransactions = $this->model
            ->with(['wallet.owner', 'order', 'relatedTransaction'])
            ->ofType($type->value)
            ->orderBy('created_at', 'desc')
            ->get();

        return $eloquentTransactions->map(fn ($transaction): Transaction => $this->mapFromEloquent($transaction))->toArray();
    }

    public function findByDateRange(Carbon $fromDate, Carbon $toDate): array
    {
        $eloquentTransactions = $this->model
            ->with(['wallet.owner', 'order', 'relatedTransaction'])
            ->whereBetween('created_at', [$fromDate->startOfDay(), $toDate->endOfDay()])
            ->orderBy('created_at', 'desc')
            ->get();

        return $eloquentTransactions->map(fn ($transaction): Transaction => $this->mapFromEloquent($transaction))->toArray();
    }

    public function bulkUpdateStatus(array $transactionIds, TransactionStatus $status): bool
    {
        if ($transactionIds === []) {
            return false;
        }

        $updated = $this->model
            ->whereIn('id', $transactionIds)
            ->update([
                'status' => $status->value,
                'updated_at' => now(),
            ]);

        // Clear caches for updated transactions
        foreach ($transactionIds as $transactionId) {
            $transaction = $this->findById($transactionId);
            if ($transaction instanceof Transaction) {
                $this->clearCache($transaction);
            }
        }

        return $updated > 0;
    }

    public function getTransactionSummary(int $walletId, Carbon $fromDate, Carbon $toDate): array
    {
        $query = $this->model
            ->where('wallet_id', $walletId)
            ->completed()
            ->whereBetween('created_at', [$fromDate->startOfDay(), $toDate->endOfDay()]);

        $credits = $query->clone()->credits()->sum('amount');
        $debits = $query->clone()->debits()->sum('amount');
        $transactionCount = $query->count();

        return [
            'wallet_id' => $walletId,
            'total_credits' => $credits,
            'total_debits' => $debits,
            'net_amount' => $credits - $debits,
            'transaction_count' => $transactionCount,
            'start_date' => $fromDate->toDateString(),
            'end_date' => $toDate->toDateString(),
        ];
    }

    public function getTransactionStatistics(): array
    {
        $stats = $this->model
            ->selectRaw('
                type,
                status,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount,
                MIN(amount) as min_amount,
                MAX(amount) as max_amount
            ')
            ->groupBy('type', 'status')
            ->get();

        return $stats->map(fn ($stat): array => [
            'type' => $stat->type,
            'status' => $stat->status,
            'count' => $stat->count,
            'total_amount' => $stat->total_amount,
            'avg_amount' => $stat->avg_amount,
            'min_amount' => $stat->min_amount,
            'max_amount' => $stat->max_amount,
        ])->toArray();
    }

    public function findExpiredPendingTransactions(Carbon $expiredBefore): array
    {
        $eloquentTransactions = $this->model
            ->with(['wallet.owner', 'order', 'relatedTransaction'])
            ->pending()
            ->where('created_at', '<', $expiredBefore)
            ->orderBy('created_at', 'asc')
            ->get();

        return $eloquentTransactions->map(fn ($transaction): Transaction => $this->mapFromEloquent($transaction))->toArray();
    }

    public function findDuplicateReferences(): array
    {
        $duplicates = $this->model
            ->selectRaw('reference, COUNT(*) as count')
            ->groupBy('reference')
            ->having('count', '>', 1)
            ->get();

        return $duplicates->pluck('reference')->toArray();
    }

    public function countByWallet(int $walletId): int
    {
        return $this->model->where('wallet_id', $walletId)->count();
    }

    public function getTotalAmountByWallet(int $walletId, ?TransactionType $type = null): array
    {
        $query = $this->model
            ->where('wallet_id', $walletId)
            ->completed();

        if ($type instanceof TransactionType) {
            $query->ofType($type->value);
        }

        $credits = $query->clone()->credits()->sum('amount');
        $debits = $query->clone()->debits()->sum('amount');

        return [
            'wallet_id' => $walletId,
            'total_credits' => $credits,
            'total_debits' => $debits,
            'net_amount' => $credits - $debits,
            'transaction_type' => $type?->value,
        ];
    }

    public function findLatestByWallet(int $walletId, int $limit = 10): array
    {
        $eloquentTransactions = $this->model
            ->with(['wallet.owner', 'order', 'relatedTransaction'])
            ->where('wallet_id', $walletId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $eloquentTransactions->map(fn ($transaction): Transaction => $this->mapFromEloquent($transaction))->toArray();
    }

    public function findByIds(array $transactionIds): array
    {
        if ($transactionIds === []) {
            return [];
        }

        $eloquentTransactions = $this->model
            ->with(['wallet.owner', 'order', 'relatedTransaction'])
            ->whereIn('id', $transactionIds)
            ->orderBy('created_at', 'desc')
            ->get();

        return $eloquentTransactions->map(fn ($transaction): Transaction => $this->mapFromEloquent($transaction))->toArray();
    }

    private function mapFromEloquent(EloquentTransaction $eloquentTransaction): Transaction
    {
        return Transaction::create(
            walletId: $eloquentTransaction->wallet_id,
            type: TransactionType::from($eloquentTransaction->type),
            amount: Money::fromAmount($eloquentTransaction->amount, $eloquentTransaction->wallet->currency ?? 'USD'),
            balanceAfter: Money::fromAmount($eloquentTransaction->balance_after, $eloquentTransaction->wallet->currency ?? 'USD'),
            reference: $eloquentTransaction->reference,
            description: $eloquentTransaction->description,
            metadata: $eloquentTransaction->metadata ?? [],
            relatedTransactionId: $eloquentTransaction->related_transaction_id,
            orderId: $eloquentTransaction->order_id
        );
    }

    private function getCacheKey(int $transactionId): string
    {
        return self::CACHE_PREFIX.':'.$transactionId;
    }

    private function getReferenceCacheKey(string $reference): string
    {
        return self::CACHE_PREFIX.':ref:'.$reference;
    }

    private function clearCache(Transaction $transaction): void
    {
        Cache::forget($this->getCacheKey($transaction->getId()));
        Cache::forget($this->getReferenceCacheKey($transaction->getReference()));
    }
}

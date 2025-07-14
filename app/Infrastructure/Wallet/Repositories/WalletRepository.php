<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet\Repositories;

use App\Application\Wallet\Contracts\WalletRepositoryInterface;
use App\Domain\Wallet\Models\Wallet;
use App\Domain\Wallet\ValueObjects\Money;
use App\Domain\Wallet\ValueObjects\WalletType;
use App\Infrastructure\Wallet\Models\EloquentWallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final readonly class WalletRepository implements WalletRepositoryInterface
{
    private const CACHE_PREFIX = 'wallet';

    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private EloquentWallet $model
    ) {}

    public function findById(int $walletId): ?Wallet
    {
        $cacheKey = $this->getCacheKey($walletId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($walletId): ?\App\Domain\Wallet\Models\Wallet {
            $eloquentWallet = $this->model
                ->with(['owner', 'transactions'])
                ->find($walletId);

            return $eloquentWallet ? $this->mapFromEloquent($eloquentWallet) : null;
        });
    }

    public function findByOwnerId(int $ownerId): array
    {
        $eloquentWallets = $this->model
            ->with(['owner', 'transactions'])
            ->where('owner_id', $ownerId)
            ->orderBy('wallet_type')
            ->get();

        return $eloquentWallets->map(fn ($wallet): Wallet => $this->mapFromEloquent($wallet))->toArray();
    }

    public function findByOwnerIdAndType(int $ownerId, WalletType $walletType): ?Wallet
    {
        $cacheKey = $this->getOwnerTypeCacheKey($ownerId, $walletType);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($ownerId, $walletType): ?\App\Domain\Wallet\Models\Wallet {
            $eloquentWallet = $this->model
                ->with(['owner', 'transactions'])
                ->where('owner_id', $ownerId)
                ->ofType($walletType->value)
                ->first();

            return $eloquentWallet ? $this->mapFromEloquent($eloquentWallet) : null;
        });
    }

    public function findByOwnerIdAndTypeWithLock(int $ownerId, WalletType $walletType): ?Wallet
    {
        $eloquentWallet = $this->model
            ->with(['owner', 'transactions'])
            ->where('owner_id', $ownerId)
            ->ofType($walletType->value)
            ->lockForUpdate()
            ->first();

        return $eloquentWallet ? $this->mapFromEloquent($eloquentWallet) : null;
    }

    public function save(Wallet $wallet): Wallet
    {
        return DB::transaction(function () use ($wallet): Wallet {
            $eloquentWallet = $this->model->find($wallet->getId());

            if (! $eloquentWallet) {
                $eloquentWallet = new EloquentWallet;
            }

            $eloquentWallet->fill([
                'owner_id' => $wallet->getOwnerId(),
                'wallet_type' => $wallet->getWalletType()->value,
                'balance' => $wallet->getBalance()->amount(),
                'locked_balance' => $wallet->getLockedBalance()->amount(),
                'currency' => $wallet->getCurrency(),
                'is_active' => $wallet->isActive(),
                'last_transaction_at' => $wallet->getLastTransactionAt(),
            ]);

            $eloquentWallet->save();

            // Clear related caches
            $this->clearCache($wallet);

            return $this->mapFromEloquent($eloquentWallet);
        });
    }

    public function saveWithLock(Wallet $wallet): Wallet
    {
        return DB::transaction(function () use ($wallet): Wallet {
            // Lock the wallet for update to prevent race conditions
            $eloquentWallet = $this->model
                ->where('id', $wallet->getId())
                ->lockForUpdate()
                ->first();

            if (! $eloquentWallet) {
                $eloquentWallet = new EloquentWallet;
            }

            $eloquentWallet->fill([
                'owner_id' => $wallet->getOwnerId(),
                'wallet_type' => $wallet->getWalletType()->value,
                'balance' => $wallet->getBalance()->amount(),
                'locked_balance' => $wallet->getLockedBalance()->amount(),
                'currency' => $wallet->getCurrency(),
                'is_active' => $wallet->isActive(),
                'last_transaction_at' => $wallet->getLastTransactionAt(),
            ]);

            $eloquentWallet->save();

            // Clear related caches
            $this->clearCache($wallet);

            return $this->mapFromEloquent($eloquentWallet);
        });
    }

    public function delete(int $walletId): bool
    {
        return DB::transaction(function () use ($walletId) {
            $eloquentWallet = $this->model->find($walletId);
            if (! $eloquentWallet) {
                return false;
            }

            $wallet = $this->mapFromEloquent($eloquentWallet);
            $deleted = $eloquentWallet->delete();

            if ($deleted) {
                $this->clearCache($wallet);
            }

            return $deleted;
        });
    }

    public function exists(int $walletId): bool
    {
        return $this->model->where('id', $walletId)->exists();
    }

    public function existsByOwnerAndType(int $ownerId, WalletType $walletType): bool
    {
        return $this->model
            ->where('owner_id', $ownerId)
            ->ofType($walletType->value)
            ->exists();
    }

    public function findActive(): array
    {
        $eloquentWallets = $this->model
            ->with(['owner', 'transactions'])
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();

        return $eloquentWallets->map(fn ($wallet): Wallet => $this->mapFromEloquent($wallet))->toArray();
    }

    public function findByType(WalletType $walletType): array
    {
        $eloquentWallets = $this->model
            ->with(['owner', 'transactions'])
            ->ofType($walletType->value)
            ->orderBy('created_at', 'desc')
            ->get();

        return $eloquentWallets->map(fn ($wallet): Wallet => $this->mapFromEloquent($wallet))->toArray();
    }

    public function findWithLowBalance(float $threshold, string $currency): array
    {
        $eloquentWallets = $this->model
            ->with(['owner', 'transactions'])
            ->withBalanceBelow($threshold)
            ->ofCurrency($currency)
            ->active()
            ->get();

        return $eloquentWallets->map(fn ($wallet): Wallet => $this->mapFromEloquent($wallet))->toArray();
    }

    public function findWithHighBalance(float $threshold, string $currency): array
    {
        $eloquentWallets = $this->model
            ->with(['owner', 'transactions'])
            ->withBalanceAbove($threshold)
            ->ofCurrency($currency)
            ->active()
            ->get();

        return $eloquentWallets->map(fn ($wallet): Wallet => $this->mapFromEloquent($wallet))->toArray();
    }

    public function getTotalBalance(string $currency): array
    {
        $result = $this->model
            ->ofCurrency($currency)
            ->active()
            ->selectRaw('
                wallet_type,
                SUM(balance) as total_balance,
                SUM(locked_balance) as total_locked_balance,
                COUNT(*) as wallet_count
            ')
            ->groupBy('wallet_type')
            ->get();

        return $result->map(fn ($item): array => [
            'wallet_type' => $item->wallet_type,
            'total_balance' => Money::fromAmount($item->total_balance, $currency),
            'total_locked_balance' => Money::fromAmount($item->total_locked_balance, $currency),
            'wallet_count' => $item->wallet_count,
        ])->toArray();
    }

    public function getBalancesByOwner(int $ownerId): array
    {
        $eloquentWallets = $this->model
            ->with(['owner'])
            ->where('owner_id', $ownerId)
            ->active()
            ->get();

        return $eloquentWallets->map(fn ($wallet): array => [
            'wallet_type' => $wallet->wallet_type,
            'balance' => Money::fromAmount($wallet->balance, $wallet->currency),
            'locked_balance' => Money::fromAmount($wallet->locked_balance, $wallet->currency),
            'currency' => $wallet->currency,
            'last_transaction_at' => $wallet->last_transaction_at,
        ])->toArray();
    }

    public function findExpiredLocks(): array
    {
        // This would be implemented if we had a lock expiration mechanism
        // For now, returning empty array
        return [];
    }

    public function bulkUpdate(array $wallets): bool
    {
        return DB::transaction(function () use ($wallets): bool {
            $updated = 0;
            foreach ($wallets as $wallet) {
                $eloquentWallet = $this->model->find($wallet->getId());
                if ($eloquentWallet) {
                    $eloquentWallet->fill([
                        'balance' => $wallet->getBalance()->amount(),
                        'locked_balance' => $wallet->getLockedBalance()->amount(),
                        'is_active' => $wallet->isActive(),
                        'last_transaction_at' => $wallet->getLastTransactionAt(),
                    ]);

                    if ($eloquentWallet->save()) {
                        ++$updated;
                        $this->clearCache($wallet);
                    }
                }
            }

            return $updated === count($wallets);
        });
    }

    public function findByIds(array $walletIds): array
    {
        if ($walletIds === []) {
            return [];
        }

        $eloquentWallets = $this->model
            ->with(['owner', 'transactions'])
            ->whereIn('id', $walletIds)
            ->get();

        return $eloquentWallets->map(fn ($wallet): Wallet => $this->mapFromEloquent($wallet))->toArray();
    }

    public function getWalletStatistics(): array
    {
        $stats = $this->model
            ->selectRaw('
                wallet_type,
                currency,
                COUNT(*) as count,
                SUM(balance) as total_balance,
                AVG(balance) as avg_balance,
                MIN(balance) as min_balance,
                MAX(balance) as max_balance,
                SUM(locked_balance) as total_locked_balance
            ')
            ->active()
            ->groupBy('wallet_type', 'currency')
            ->get();

        return $stats->map(fn ($stat): array => [
            'wallet_type' => $stat->wallet_type,
            'currency' => $stat->currency,
            'count' => $stat->count,
            'total_balance' => Money::fromAmount($stat->total_balance, $stat->currency),
            'avg_balance' => Money::fromAmount($stat->avg_balance, $stat->currency),
            'min_balance' => Money::fromAmount($stat->min_balance, $stat->currency),
            'max_balance' => Money::fromAmount($stat->max_balance, $stat->currency),
            'total_locked_balance' => Money::fromAmount($stat->total_locked_balance, $stat->currency),
        ])->toArray();
    }

    private function mapFromEloquent(EloquentWallet $eloquentWallet): Wallet
    {
        return new Wallet(
            id: $eloquentWallet->id,
            ownerId: $eloquentWallet->owner_id,
            walletType: WalletType::from($eloquentWallet->wallet_type),
            balance: Money::fromAmount($eloquentWallet->balance, $eloquentWallet->currency),
            lockedBalance: Money::fromAmount($eloquentWallet->locked_balance, $eloquentWallet->currency),
            currency: $eloquentWallet->currency,
            isActive: $eloquentWallet->is_active,
            lastTransactionAt: $eloquentWallet->last_transaction_at ? Carbon::parse($eloquentWallet->last_transaction_at) : null,
            createdAt: Carbon::parse($eloquentWallet->created_at),
            updatedAt: Carbon::parse($eloquentWallet->updated_at)
        );
    }

    private function getCacheKey(int $walletId): string
    {
        return self::CACHE_PREFIX.':'.$walletId;
    }

    private function getOwnerTypeCacheKey(int $ownerId, WalletType $walletType): string
    {
        return self::CACHE_PREFIX.':owner:'.$ownerId.':type:'.$walletType->value;
    }

    private function clearCache(Wallet $wallet): void
    {
        Cache::forget($this->getCacheKey($wallet->getId()));
        Cache::forget($this->getOwnerTypeCacheKey($wallet->getOwnerId(), $wallet->getWalletType()));
    }
}

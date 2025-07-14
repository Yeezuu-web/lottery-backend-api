<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

use App\Domain\Wallet\Models\Wallet;
use App\Domain\Wallet\ValueObjects\WalletType;

interface WalletRepositoryInterface
{
    public function findById(int $walletId): ?Wallet;

    public function findByOwnerId(int $ownerId): array;

    public function findByOwnerIdAndType(int $ownerId, WalletType $walletType): ?Wallet;

    public function save(Wallet $wallet): Wallet;

    public function delete(int $walletId): bool;

    public function exists(int $walletId): bool;

    public function existsByOwnerAndType(int $ownerId, WalletType $walletType): bool;

    public function findActive(): array;

    public function findByType(WalletType $walletType): array;

    public function findWithLowBalance(float $threshold, string $currency): array;

    public function findWithHighBalance(float $threshold, string $currency): array;

    public function getTotalBalance(string $currency): array;

    public function getBalancesByOwner(int $ownerId): array;

    public function findExpiredLocks(): array;

    public function bulkUpdate(array $wallets): bool;

    public function findByIds(array $walletIds): array;

    public function getWalletStatistics(): array;
}

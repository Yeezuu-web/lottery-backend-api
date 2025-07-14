<?php

namespace App\Application\Wallet\Responses;

use App\Domain\Wallet\Models\Wallet;

final class WalletResponse
{
    public function __construct(
        public readonly int $id,
        public readonly int $ownerId,
        public readonly string $walletType,
        public readonly array $balance,
        public readonly array $lockedBalance,
        public readonly array $availableBalance,
        public readonly string $currency,
        public readonly bool $isActive,
        public readonly ?string $lastTransactionAt,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?array $transactions = null
    ) {}

    public static function fromDomain(Wallet $wallet, ?array $transactions = null): self
    {
        return new self(
            id: $wallet->getId(),
            ownerId: $wallet->getOwnerId(),
            walletType: $wallet->getWalletType()->value,
            balance: $wallet->getBalance()->toArray(),
            lockedBalance: $wallet->getLockedBalance()->toArray(),
            availableBalance: $wallet->getAvailableBalance()->toArray(),
            currency: $wallet->getCurrency(),
            isActive: $wallet->isActive(),
            lastTransactionAt: $wallet->getLastTransactionAt()?->toISOString(),
            createdAt: $wallet->getCreatedAt()->toISOString(),
            updatedAt: $wallet->getUpdatedAt()->toISOString(),
            transactions: $transactions
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->ownerId,
            'wallet_type' => $this->walletType,
            'balance' => $this->balance,
            'locked_balance' => $this->lockedBalance,
            'available_balance' => $this->availableBalance,
            'currency' => $this->currency,
            'is_active' => $this->isActive,
            'last_transaction_at' => $this->lastTransactionAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'transactions' => $this->transactions,
        ];
    }
}

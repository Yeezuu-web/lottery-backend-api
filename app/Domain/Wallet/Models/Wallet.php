<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Models;

use App\Domain\Wallet\Exceptions\WalletException;
use App\Domain\Wallet\ValueObjects\Money;
use App\Domain\Wallet\ValueObjects\WalletType;
use Carbon\Carbon;

final readonly class Wallet
{
    public function __construct(
        private int $id,
        private int $ownerId,
        private WalletType $walletType,
        private Money $balance,
        private Money $lockedBalance,
        private string $currency,
        private bool $isActive,
        private ?Carbon $lastTransactionAt,
        private Carbon $createdAt,
        private Carbon $updatedAt
    ) {
        $this->validateBalance();
    }

    public static function create(
        int $ownerId,
        WalletType $walletType,
        string $currency = 'KHR',
        bool $isActive = true
    ): self {
        return new self(
            id: 0, // Will be set by repository
            ownerId: $ownerId,
            walletType: $walletType,
            balance: Money::zero($currency),
            lockedBalance: Money::zero($currency),
            currency: $currency,
            isActive: $isActive,
            lastTransactionAt: null,
            createdAt: Carbon::now(),
            updatedAt: Carbon::now()
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    public function getWalletType(): WalletType
    {
        return $this->walletType;
    }

    public function getBalance(): Money
    {
        return $this->balance;
    }

    public function getLockedBalance(): Money
    {
        return $this->lockedBalance;
    }

    public function getAvailableBalance(): Money
    {
        return $this->balance->subtract($this->lockedBalance);
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getLastTransactionAt(): ?Carbon
    {
        return $this->lastTransactionAt;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }

    public function canDebit(Money $amount): bool
    {
        if (! $this->isActive) {
            return false;
        }

        if (! $amount->isSameCurrency($this->balance)) {
            return false;
        }

        return $this->getAvailableBalance()->isGreaterThanOrEqual($amount);
    }

    public function canCredit(Money $amount): bool
    {
        if (! $this->isActive) {
            return false;
        }

        return $amount->isSameCurrency($this->balance);
    }

    public function credit(Money $amount): self
    {
        if (! $this->canCredit($amount)) {
            throw WalletException::cannotCredit($this->id, $amount);
        }

        $newBalance = $this->balance->add($amount);

        return new self(
            id: $this->id,
            ownerId: $this->ownerId,
            walletType: $this->walletType,
            balance: $newBalance,
            lockedBalance: $this->lockedBalance,
            currency: $this->currency,
            isActive: $this->isActive,
            lastTransactionAt: Carbon::now(),
            createdAt: $this->createdAt,
            updatedAt: Carbon::now()
        );
    }

    public function debit(Money $amount): self
    {
        if (! $this->canDebit($amount)) {
            throw WalletException::insufficientFunds($this->id, $amount, $this->getAvailableBalance());
        }

        $newBalance = $this->balance->subtract($amount);

        return new self(
            id: $this->id,
            ownerId: $this->ownerId,
            walletType: $this->walletType,
            balance: $newBalance,
            lockedBalance: $this->lockedBalance,
            currency: $this->currency,
            isActive: $this->isActive,
            lastTransactionAt: Carbon::now(),
            createdAt: $this->createdAt,
            updatedAt: Carbon::now()
        );
    }

    public function lock(Money $amount): self
    {
        if (! $this->canDebit($amount)) {
            throw WalletException::cannotLock($this->id, $amount);
        }

        $newLockedBalance = $this->lockedBalance->add($amount);

        return new self(
            id: $this->id,
            ownerId: $this->ownerId,
            walletType: $this->walletType,
            balance: $this->balance,
            lockedBalance: $newLockedBalance,
            currency: $this->currency,
            isActive: $this->isActive,
            lastTransactionAt: $this->lastTransactionAt,
            createdAt: $this->createdAt,
            updatedAt: Carbon::now()
        );
    }

    public function unlock(Money $amount): self
    {
        if ($this->lockedBalance->isLessThan($amount)) {
            throw WalletException::cannotUnlock($this->id, $amount);
        }

        $newLockedBalance = $this->lockedBalance->subtract($amount);

        return new self(
            id: $this->id,
            ownerId: $this->ownerId,
            walletType: $this->walletType,
            balance: $this->balance,
            lockedBalance: $newLockedBalance,
            currency: $this->currency,
            isActive: $this->isActive,
            lastTransactionAt: $this->lastTransactionAt,
            createdAt: $this->createdAt,
            updatedAt: Carbon::now()
        );
    }

    public function activate(): self
    {
        return new self(
            id: $this->id,
            ownerId: $this->ownerId,
            walletType: $this->walletType,
            balance: $this->balance,
            lockedBalance: $this->lockedBalance,
            currency: $this->currency,
            isActive: true,
            lastTransactionAt: $this->lastTransactionAt,
            createdAt: $this->createdAt,
            updatedAt: Carbon::now()
        );
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            ownerId: $this->ownerId,
            walletType: $this->walletType,
            balance: $this->balance,
            lockedBalance: $this->lockedBalance,
            currency: $this->currency,
            isActive: false,
            lastTransactionAt: $this->lastTransactionAt,
            createdAt: $this->createdAt,
            updatedAt: Carbon::now()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->ownerId,
            'wallet_type' => $this->walletType->value,
            'balance' => $this->balance->toArray(),
            'locked_balance' => $this->lockedBalance->toArray(),
            'available_balance' => $this->getAvailableBalance()->toArray(),
            'currency' => $this->currency,
            'is_active' => $this->isActive,
            'last_transaction_at' => $this->lastTransactionAt?->toISOString(),
            'created_at' => $this->createdAt->toISOString(),
            'updated_at' => $this->updatedAt->toISOString(),
        ];
    }

    private function validateBalance(): void
    {
        if ($this->balance->isNegative()) {
            throw WalletException::negativeBalance($this->id);
        }

        if ($this->lockedBalance->isNegative()) {
            throw WalletException::negativeLockedBalance($this->id);
        }

        if ($this->lockedBalance->isGreaterThan($this->balance)) {
            throw WalletException::lockedBalanceExceedsBalance($this->id);
        }
    }
}

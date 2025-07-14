<?php

namespace App\Domain\Wallet\Exceptions;

use App\Domain\Wallet\ValueObjects\Money;
use Exception;

class WalletException extends Exception
{
    public static function notFound(int $walletId): self
    {
        return new self("Wallet with ID {$walletId} not found");
    }

    public static function alreadyExists(int $ownerId, string $walletType): self
    {
        return new self("Wallet of type {$walletType} already exists for owner {$ownerId}");
    }

    public static function inactive(int $walletId): self
    {
        return new self("Wallet {$walletId} is inactive");
    }

    public static function insufficientFunds(int $walletId, Money $requested, Money $available): self
    {
        return new self(
            "Insufficient funds in wallet {$walletId}. Requested: {$requested}, Available: {$available}"
        );
    }

    public static function cannotCredit(int $walletId, Money $amount): self
    {
        return new self("Cannot credit {$amount} to wallet {$walletId}");
    }

    public static function cannotDebit(int $walletId, Money $amount): self
    {
        return new self("Cannot debit {$amount} from wallet {$walletId}");
    }

    public static function cannotLock(int $walletId, Money $amount): self
    {
        return new self("Cannot lock {$amount} in wallet {$walletId}");
    }

    public static function cannotUnlock(int $walletId, Money $amount): self
    {
        return new self("Cannot unlock {$amount} from wallet {$walletId}");
    }

    public static function negativeBalance(int $walletId): self
    {
        return new self("Wallet {$walletId} cannot have negative balance");
    }

    public static function negativeLockedBalance(int $walletId): self
    {
        return new self("Wallet {$walletId} cannot have negative locked balance");
    }

    public static function lockedBalanceExceedsBalance(int $walletId): self
    {
        return new self("Locked balance cannot exceed total balance in wallet {$walletId}");
    }

    public static function transferNotAllowed(string $fromType, string $toType): self
    {
        return new self("Transfer from {$fromType} to {$toType} is not allowed");
    }

    public static function currencyMismatch(string $fromCurrency, string $toCurrency): self
    {
        return new self("Currency mismatch: cannot transfer from {$fromCurrency} to {$toCurrency}");
    }

    public static function operationNotAllowed(int $walletId, string $operation): self
    {
        return new self("Operation {$operation} is not allowed on wallet {$walletId}");
    }

    public static function concurrentModification(int $walletId): self
    {
        return new self("Wallet {$walletId} was modified by another process");
    }

    public static function invalidOwner(int $walletId, int $expectedOwnerId, int $actualOwnerId): self
    {
        return new self(
            "Wallet {$walletId} owner mismatch. Expected: {$expectedOwnerId}, Actual: {$actualOwnerId}"
        );
    }
}

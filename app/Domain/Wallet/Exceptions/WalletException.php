<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Exceptions;

use App\Domain\Wallet\ValueObjects\Money;
use Exception;

final class WalletException extends Exception
{
    public static function notFound(int $walletId): self
    {
        return new self(sprintf('Wallet with ID %d not found', $walletId));
    }

    public static function notFoundWallet(): self
    {
        return new self('Wallet for this user is not found.');
    }

    public static function alreadyExists(int $ownerId, string $walletType): self
    {
        return new self(sprintf('Wallet of type %s already exists for owner %d', $walletType, $ownerId));
    }

    public static function inactive(int $walletId): self
    {
        return new self(sprintf('Wallet %d is inactive', $walletId));
    }

    public static function insufficientFunds(int $walletId, Money $requested, Money $available): self
    {
        return new self(
            sprintf('Insufficient funds in wallet %d. Requested: %s, Available: %s', $walletId, $requested, $available)
        );
    }

    public static function cannotCredit(int $walletId, Money $amount): self
    {
        return new self(sprintf('Cannot credit %s to wallet %d', $amount, $walletId));
    }

    public static function cannotDebit(int $walletId, Money $amount): self
    {
        return new self(sprintf('Cannot debit %s from wallet %d', $amount, $walletId));
    }

    public static function cannotLock(int $walletId, Money $amount): self
    {
        return new self(sprintf('Cannot lock %s in wallet %d', $amount, $walletId));
    }

    public static function cannotUnlock(int $walletId, Money $amount): self
    {
        return new self(sprintf('Cannot unlock %s from wallet %d', $amount, $walletId));
    }

    public static function negativeBalance(int $walletId): self
    {
        return new self(sprintf('Wallet %d cannot have negative balance', $walletId));
    }

    public static function negativeLockedBalance(int $walletId): self
    {
        return new self(sprintf('Wallet %d cannot have negative locked balance', $walletId));
    }

    public static function lockedBalanceExceedsBalance(int $walletId): self
    {
        return new self('Locked balance cannot exceed total balance in wallet '.$walletId);
    }

    public static function transferNotAllowed(string $fromType, string $toType): self
    {
        return new self(sprintf('Transfer from %s to %s is not allowed', $fromType, $toType));
    }

    public static function currencyMismatch(string $fromCurrency, string $toCurrency): self
    {
        return new self(sprintf('Currency mismatch: cannot transfer from %s to %s', $fromCurrency, $toCurrency));
    }

    public static function operationNotAllowed(int $walletId, string $operation): self
    {
        return new self(sprintf('Operation %s is not allowed on wallet %d', $operation, $walletId));
    }

    public static function concurrentModification(int $walletId): self
    {
        return new self(sprintf('Wallet %d was modified by another process', $walletId));
    }

    public static function invalidOwner(int $walletId, int $expectedOwnerId, int $actualOwnerId): self
    {
        return new self(
            sprintf('Wallet %d owner mismatch. Expected: %d, Actual: %d', $walletId, $expectedOwnerId, $actualOwnerId)
        );
    }
}

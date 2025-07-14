<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Exceptions;

use App\Domain\Wallet\ValueObjects\TransactionStatus;
use Exception;

final class TransactionException extends Exception
{
    public static function notFound(int $transactionId): self
    {
        return new self(sprintf('Transaction with ID %d not found', $transactionId));
    }

    public static function alreadyExists(string $reference): self
    {
        return new self(sprintf('Transaction with reference %s already exists', $reference));
    }

    public static function cannotComplete(int $transactionId, TransactionStatus $currentStatus): self
    {
        return new self(
            sprintf('Cannot complete transaction %d in status %s', $transactionId, $currentStatus->value)
        );
    }

    public static function cannotFail(int $transactionId, TransactionStatus $currentStatus): self
    {
        return new self(
            sprintf('Cannot fail transaction %d in status %s', $transactionId, $currentStatus->value)
        );
    }

    public static function cannotCancel(int $transactionId, TransactionStatus $currentStatus): self
    {
        return new self(
            sprintf('Cannot cancel transaction %d in status %s', $transactionId, $currentStatus->value)
        );
    }

    public static function cannotReverse(int $transactionId, TransactionStatus $currentStatus): self
    {
        return new self(
            sprintf('Cannot reverse transaction %d in status %s', $transactionId, $currentStatus->value)
        );
    }

    public static function invalidStatusTransition(
        int $transactionId,
        TransactionStatus $fromStatus,
        TransactionStatus $toStatus
    ): self {
        return new self(
            sprintf('Invalid status transition for transaction %d: %s -> %s', $transactionId, $fromStatus->value, $toStatus->value)
        );
    }

    public static function negativeAmount(int $transactionId): self
    {
        return new self(sprintf('Transaction %d cannot have negative amount', $transactionId));
    }

    public static function negativeBalanceAfter(int $transactionId): self
    {
        return new self(sprintf('Transaction %d cannot result in negative balance', $transactionId));
    }

    public static function emptyReference(int $transactionId): self
    {
        return new self(sprintf('Transaction %d must have a reference', $transactionId));
    }

    public static function invalidReference(int $transactionId, string $reference): self
    {
        return new self(sprintf('Transaction %d has invalid reference: %s', $transactionId, $reference));
    }

    public static function duplicateReference(string $reference): self
    {
        return new self(sprintf('Transaction with reference %s already exists', $reference));
    }

    public static function walletMismatch(int $transactionId, int $expectedWalletId, int $actualWalletId): self
    {
        return new self(
            sprintf('Transaction %d wallet mismatch. Expected: %d, Actual: %d', $transactionId, $expectedWalletId, $actualWalletId)
        );
    }

    public static function concurrentModification(int $transactionId): self
    {
        return new self(sprintf('Transaction %d was modified by another process', $transactionId));
    }

    public static function processingTimeout(int $transactionId): self
    {
        return new self(sprintf('Transaction %d processing timeout', $transactionId));
    }

    public static function relationshipViolation(int $transactionId, string $details): self
    {
        return new self(sprintf('Transaction %d relationship violation: %s', $transactionId, $details));
    }

    public static function insufficientMetadata(int $transactionId, array $requiredFields): self
    {
        $fields = implode(', ', $requiredFields);

        return new self(sprintf('Transaction %d missing required metadata: %s', $transactionId, $fields));
    }

    public static function operationNotAllowed(int $transactionId, string $operation): self
    {
        return new self(sprintf('Operation %s not allowed on transaction %d', $operation, $transactionId));
    }
}

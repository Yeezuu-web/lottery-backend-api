<?php

namespace App\Domain\Wallet\Exceptions;

use App\Domain\Wallet\ValueObjects\TransactionStatus;
use Exception;

class TransactionException extends Exception
{
    public static function notFound(int $transactionId): self
    {
        return new self("Transaction with ID {$transactionId} not found");
    }

    public static function alreadyExists(string $reference): self
    {
        return new self("Transaction with reference {$reference} already exists");
    }

    public static function cannotComplete(int $transactionId, TransactionStatus $currentStatus): self
    {
        return new self(
            "Cannot complete transaction {$transactionId} in status {$currentStatus->value}"
        );
    }

    public static function cannotFail(int $transactionId, TransactionStatus $currentStatus): self
    {
        return new self(
            "Cannot fail transaction {$transactionId} in status {$currentStatus->value}"
        );
    }

    public static function cannotCancel(int $transactionId, TransactionStatus $currentStatus): self
    {
        return new self(
            "Cannot cancel transaction {$transactionId} in status {$currentStatus->value}"
        );
    }

    public static function cannotReverse(int $transactionId, TransactionStatus $currentStatus): self
    {
        return new self(
            "Cannot reverse transaction {$transactionId} in status {$currentStatus->value}"
        );
    }

    public static function invalidStatusTransition(
        int $transactionId,
        TransactionStatus $fromStatus,
        TransactionStatus $toStatus
    ): self {
        return new self(
            "Invalid status transition for transaction {$transactionId}: {$fromStatus->value} -> {$toStatus->value}"
        );
    }

    public static function negativeAmount(int $transactionId): self
    {
        return new self("Transaction {$transactionId} cannot have negative amount");
    }

    public static function negativeBalanceAfter(int $transactionId): self
    {
        return new self("Transaction {$transactionId} cannot result in negative balance");
    }

    public static function emptyReference(int $transactionId): self
    {
        return new self("Transaction {$transactionId} must have a reference");
    }

    public static function invalidReference(int $transactionId, string $reference): self
    {
        return new self("Transaction {$transactionId} has invalid reference: {$reference}");
    }

    public static function duplicateReference(string $reference): self
    {
        return new self("Transaction with reference {$reference} already exists");
    }

    public static function walletMismatch(int $transactionId, int $expectedWalletId, int $actualWalletId): self
    {
        return new self(
            "Transaction {$transactionId} wallet mismatch. Expected: {$expectedWalletId}, Actual: {$actualWalletId}"
        );
    }

    public static function concurrentModification(int $transactionId): self
    {
        return new self("Transaction {$transactionId} was modified by another process");
    }

    public static function processingTimeout(int $transactionId): self
    {
        return new self("Transaction {$transactionId} processing timeout");
    }

    public static function relationshipViolation(int $transactionId, string $details): self
    {
        return new self("Transaction {$transactionId} relationship violation: {$details}");
    }

    public static function insufficientMetadata(int $transactionId, array $requiredFields): self
    {
        $fields = implode(', ', $requiredFields);

        return new self("Transaction {$transactionId} missing required metadata: {$fields}");
    }

    public static function operationNotAllowed(int $transactionId, string $operation): self
    {
        return new self("Operation {$operation} not allowed on transaction {$transactionId}");
    }
}

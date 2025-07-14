<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Models;

use App\Domain\Wallet\Exceptions\TransactionException;
use App\Domain\Wallet\ValueObjects\Money;
use App\Domain\Wallet\ValueObjects\TransactionStatus;
use App\Domain\Wallet\ValueObjects\TransactionType;
use Carbon\Carbon;

final readonly class Transaction
{
    public function __construct(
        private int $id,
        private int $walletId,
        private TransactionType $type,
        private Money $amount,
        private Money $balanceAfter,
        private string $reference,
        private string $description,
        private TransactionStatus $status,
        private ?array $metadata,
        private ?int $relatedTransactionId,
        private ?int $orderId,
        private Carbon $createdAt,
        private Carbon $updatedAt
    ) {
        $this->validateTransaction();
    }

    public static function create(
        int $walletId,
        TransactionType $type,
        Money $amount,
        Money $balanceAfter,
        string $reference,
        string $description,
        ?array $metadata = null,
        ?int $relatedTransactionId = null,
        ?int $orderId = null
    ): self {
        return new self(
            id: 0, // Will be set by repository
            walletId: $walletId,
            type: $type,
            amount: $amount,
            balanceAfter: $balanceAfter,
            reference: $reference,
            description: $description,
            status: TransactionStatus::PENDING,
            metadata: $metadata,
            relatedTransactionId: $relatedTransactionId,
            orderId: $orderId,
            createdAt: Carbon::now(),
            updatedAt: Carbon::now()
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getWalletId(): int
    {
        return $this->walletId;
    }

    public function getType(): TransactionType
    {
        return $this->type;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getBalanceAfter(): Money
    {
        return $this->balanceAfter;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStatus(): TransactionStatus
    {
        return $this->status;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getRelatedTransactionId(): ?int
    {
        return $this->relatedTransactionId;
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }

    public function isPending(): bool
    {
        return $this->status === TransactionStatus::PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === TransactionStatus::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === TransactionStatus::FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status === TransactionStatus::CANCELLED;
    }

    public function isCredit(): bool
    {
        return $this->type === TransactionType::CREDIT;
    }

    public function isDebit(): bool
    {
        return $this->type === TransactionType::DEBIT;
    }

    public function complete(): self
    {
        if (! $this->isPending()) {
            throw TransactionException::cannotComplete($this->id, $this->status);
        }

        return new self(
            id: $this->id,
            walletId: $this->walletId,
            type: $this->type,
            amount: $this->amount,
            balanceAfter: $this->balanceAfter,
            reference: $this->reference,
            description: $this->description,
            status: TransactionStatus::COMPLETED,
            metadata: $this->metadata,
            relatedTransactionId: $this->relatedTransactionId,
            orderId: $this->orderId,
            createdAt: $this->createdAt,
            updatedAt: Carbon::now()
        );
    }

    public function fail(string $reason): self
    {
        if (! $this->isPending()) {
            throw TransactionException::cannotFail($this->id, $this->status);
        }

        $newMetadata = $this->metadata ?? [];
        $newMetadata['failure_reason'] = $reason;
        $newMetadata['failed_at'] = Carbon::now()->toISOString();

        return new self(
            id: $this->id,
            walletId: $this->walletId,
            type: $this->type,
            amount: $this->amount,
            balanceAfter: $this->balanceAfter,
            reference: $this->reference,
            description: $this->description,
            status: TransactionStatus::FAILED,
            metadata: $newMetadata,
            relatedTransactionId: $this->relatedTransactionId,
            orderId: $this->orderId,
            createdAt: $this->createdAt,
            updatedAt: Carbon::now()
        );
    }

    public function cancel(string $reason): self
    {
        if (! $this->isPending()) {
            throw TransactionException::cannotCancel($this->id, $this->status);
        }

        $newMetadata = $this->metadata ?? [];
        $newMetadata['cancellation_reason'] = $reason;
        $newMetadata['cancelled_at'] = Carbon::now()->toISOString();

        return new self(
            id: $this->id,
            walletId: $this->walletId,
            type: $this->type,
            amount: $this->amount,
            balanceAfter: $this->balanceAfter,
            reference: $this->reference,
            description: $this->description,
            status: TransactionStatus::CANCELLED,
            metadata: $newMetadata,
            relatedTransactionId: $this->relatedTransactionId,
            orderId: $this->orderId,
            createdAt: $this->createdAt,
            updatedAt: Carbon::now()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->walletId,
            'type' => $this->type->value,
            'amount' => $this->amount->toArray(),
            'balance_after' => $this->balanceAfter->toArray(),
            'reference' => $this->reference,
            'description' => $this->description,
            'status' => $this->status->value,
            'metadata' => $this->metadata,
            'related_transaction_id' => $this->relatedTransactionId,
            'order_id' => $this->orderId,
            'created_at' => $this->createdAt->toISOString(),
            'updated_at' => $this->updatedAt->toISOString(),
        ];
    }

    private function validateTransaction(): void
    {
        if ($this->amount->isNegative()) {
            throw TransactionException::negativeAmount($this->id);
        }

        if ($this->balanceAfter->isNegative()) {
            throw TransactionException::negativeBalanceAfter($this->id);
        }

        if ($this->reference === '' || $this->reference === '0') {
            throw TransactionException::emptyReference($this->id);
        }
    }
}

<?php

namespace App\Application\Wallet\Responses;

use App\Domain\Wallet\Models\Transaction;

final class TransactionResponse
{
    public function __construct(
        public readonly int $id,
        public readonly int $walletId,
        public readonly string $type,
        public readonly array $amount,
        public readonly array $balanceAfter,
        public readonly string $reference,
        public readonly string $description,
        public readonly string $status,
        public readonly ?array $metadata,
        public readonly ?int $relatedTransactionId,
        public readonly ?int $orderId,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    public static function fromDomain(Transaction $transaction): self
    {
        return new self(
            id: $transaction->getId(),
            walletId: $transaction->getWalletId(),
            type: $transaction->getType()->value,
            amount: $transaction->getAmount()->toArray(),
            balanceAfter: $transaction->getBalanceAfter()->toArray(),
            reference: $transaction->getReference(),
            description: $transaction->getDescription(),
            status: $transaction->getStatus()->value,
            metadata: $transaction->getMetadata(),
            relatedTransactionId: $transaction->getRelatedTransactionId(),
            orderId: $transaction->getOrderId(),
            createdAt: $transaction->getCreatedAt()->toISOString(),
            updatedAt: $transaction->getUpdatedAt()->toISOString()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->walletId,
            'type' => $this->type,
            'amount' => $this->amount,
            'balance_after' => $this->balanceAfter,
            'reference' => $this->reference,
            'description' => $this->description,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'related_transaction_id' => $this->relatedTransactionId,
            'order_id' => $this->orderId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}

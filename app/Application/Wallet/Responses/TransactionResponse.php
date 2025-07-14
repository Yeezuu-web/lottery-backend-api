<?php

declare(strict_types=1);

namespace App\Application\Wallet\Responses;

use App\Domain\Wallet\Models\Transaction;

final readonly class TransactionResponse
{
    public function __construct(
        public int $id,
        public int $walletId,
        public string $type,
        public array $amount,
        public array $balanceAfter,
        public string $reference,
        public string $description,
        public string $status,
        public ?array $metadata,
        public ?int $relatedTransactionId,
        public ?int $orderId,
        public string $createdAt,
        public string $updatedAt
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

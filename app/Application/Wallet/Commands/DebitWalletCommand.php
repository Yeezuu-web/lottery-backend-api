<?php

namespace App\Application\Wallet\Commands;

use App\Domain\Wallet\ValueObjects\Money;
use App\Domain\Wallet\ValueObjects\TransactionType;

final class DebitWalletCommand
{
    public function __construct(
        public readonly int $walletId,
        public readonly Money $amount,
        public readonly TransactionType $transactionType,
        public readonly string $reference,
        public readonly string $description,
        public readonly ?array $metadata = null,
        public readonly ?int $orderId = null,
        public readonly ?int $relatedTransactionId = null
    ) {}

    public function toArray(): array
    {
        return [
            'wallet_id' => $this->walletId,
            'amount' => $this->amount->toArray(),
            'transaction_type' => $this->transactionType->value,
            'reference' => $this->reference,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'order_id' => $this->orderId,
            'related_transaction_id' => $this->relatedTransactionId,
        ];
    }
}

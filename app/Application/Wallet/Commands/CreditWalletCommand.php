<?php

declare(strict_types=1);

namespace App\Application\Wallet\Commands;

use App\Domain\Wallet\ValueObjects\Money;
use App\Domain\Wallet\ValueObjects\TransactionType;

final readonly class CreditWalletCommand
{
    public function __construct(
        public int $walletId,
        public Money $amount,
        public TransactionType $transactionType,
        public string $reference,
        public string $description,
        public ?array $metadata = null,
        public ?int $orderId = null,
        public ?int $relatedTransactionId = null
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

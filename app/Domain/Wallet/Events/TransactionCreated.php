<?php

namespace App\Domain\Wallet\Events;

use App\Domain\Wallet\Models\Transaction;
use Carbon\Carbon;

final class TransactionCreated
{
    public function __construct(
        public readonly int $transactionId,
        public readonly int $walletId,
        public readonly string $type,
        public readonly array $amount,
        public readonly string $reference,
        public readonly string $description,
        public readonly Carbon $occurredAt
    ) {}

    public static function create(Transaction $transaction): self
    {
        return new self(
            transactionId: $transaction->getId(),
            walletId: $transaction->getWalletId(),
            type: $transaction->getType()->value,
            amount: $transaction->getAmount()->toArray(),
            reference: $transaction->getReference(),
            description: $transaction->getDescription(),
            occurredAt: Carbon::now()
        );
    }

    public function toArray(): array
    {
        return [
            'event' => 'transaction_created',
            'transaction_id' => $this->transactionId,
            'wallet_id' => $this->walletId,
            'type' => $this->type,
            'amount' => $this->amount,
            'reference' => $this->reference,
            'description' => $this->description,
            'occurred_at' => $this->occurredAt->toISOString(),
        ];
    }
}

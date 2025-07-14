<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Events;

use App\Domain\Wallet\Models\Transaction;
use Carbon\Carbon;

final readonly class TransactionCreated
{
    public function __construct(
        public int $transactionId,
        public int $walletId,
        public string $type,
        public array $amount,
        public string $reference,
        public string $description,
        public Carbon $occurredAt
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

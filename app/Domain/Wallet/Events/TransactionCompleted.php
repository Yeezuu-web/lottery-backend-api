<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Events;

use App\Domain\Wallet\Models\Transaction;
use Carbon\Carbon;

final readonly class TransactionCompleted
{
    public function __construct(
        public int $transactionId,
        public int $walletId,
        public string $type,
        public array $amount,
        public string $reference,
        public array $balanceAfter,
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
            balanceAfter: $transaction->getBalanceAfter()->toArray(),
            occurredAt: Carbon::now()
        );
    }

    public function toArray(): array
    {
        return [
            'event' => 'transaction_completed',
            'transaction_id' => $this->transactionId,
            'wallet_id' => $this->walletId,
            'type' => $this->type,
            'amount' => $this->amount,
            'reference' => $this->reference,
            'balance_after' => $this->balanceAfter,
            'occurred_at' => $this->occurredAt->toISOString(),
        ];
    }
}

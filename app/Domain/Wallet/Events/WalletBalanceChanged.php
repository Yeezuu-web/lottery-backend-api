<?php

namespace App\Domain\Wallet\Events;

use App\Domain\Wallet\Models\Wallet;
use App\Domain\Wallet\ValueObjects\Money;
use Carbon\Carbon;

final class WalletBalanceChanged
{
    public function __construct(
        public readonly int $walletId,
        public readonly int $ownerId,
        public readonly array $previousBalance,
        public readonly array $newBalance,
        public readonly string $reason,
        public readonly ?int $transactionId,
        public readonly Carbon $occurredAt
    ) {}

    public static function create(
        Wallet $wallet,
        Money $previousBalance,
        string $reason,
        ?int $transactionId = null
    ): self {
        return new self(
            walletId: $wallet->getId(),
            ownerId: $wallet->getOwnerId(),
            previousBalance: $previousBalance->toArray(),
            newBalance: $wallet->getBalance()->toArray(),
            reason: $reason,
            transactionId: $transactionId,
            occurredAt: Carbon::now()
        );
    }

    public function toArray(): array
    {
        return [
            'event' => 'wallet_balance_changed',
            'wallet_id' => $this->walletId,
            'owner_id' => $this->ownerId,
            'previous_balance' => $this->previousBalance,
            'new_balance' => $this->newBalance,
            'reason' => $this->reason,
            'transaction_id' => $this->transactionId,
            'occurred_at' => $this->occurredAt->toISOString(),
        ];
    }
}

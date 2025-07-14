<?php

namespace App\Domain\Wallet\Events;

use App\Domain\Wallet\Models\Wallet;
use Carbon\Carbon;

final class WalletCreated
{
    public function __construct(
        public readonly int $walletId,
        public readonly int $ownerId,
        public readonly string $walletType,
        public readonly string $currency,
        public readonly Carbon $occurredAt
    ) {}

    public static function create(Wallet $wallet): self
    {
        return new self(
            walletId: $wallet->getId(),
            ownerId: $wallet->getOwnerId(),
            walletType: $wallet->getWalletType()->value,
            currency: $wallet->getCurrency(),
            occurredAt: Carbon::now()
        );
    }

    public function toArray(): array
    {
        return [
            'event' => 'wallet_created',
            'wallet_id' => $this->walletId,
            'owner_id' => $this->ownerId,
            'wallet_type' => $this->walletType,
            'currency' => $this->currency,
            'occurred_at' => $this->occurredAt->toISOString(),
        ];
    }
}

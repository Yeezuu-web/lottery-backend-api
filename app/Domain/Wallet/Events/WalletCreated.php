<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Events;

use App\Domain\Wallet\Models\Wallet;
use Carbon\Carbon;

final readonly class WalletCreated
{
    public function __construct(
        public int $walletId,
        public int $ownerId,
        public string $walletType,
        public string $currency,
        public Carbon $occurredAt
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

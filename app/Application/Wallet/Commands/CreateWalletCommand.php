<?php

namespace App\Application\Wallet\Commands;

use App\Domain\Wallet\ValueObjects\WalletType;

final class CreateWalletCommand
{
    public function __construct(
        public readonly int $ownerId,
        public readonly WalletType $walletType,
        public readonly string $currency = 'KHR',
        public readonly bool $isActive = true,
        public readonly ?array $metadata = null
    ) {}

    public function toArray(): array
    {
        return [
            'owner_id' => $this->ownerId,
            'wallet_type' => $this->walletType->value,
            'currency' => $this->currency,
            'is_active' => $this->isActive,
            'metadata' => $this->metadata,
        ];
    }
}

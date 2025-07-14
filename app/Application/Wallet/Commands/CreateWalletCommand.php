<?php

declare(strict_types=1);

namespace App\Application\Wallet\Commands;

use App\Domain\Wallet\ValueObjects\WalletType;

final readonly class CreateWalletCommand
{
    public function __construct(
        public int $ownerId,
        public WalletType $walletType,
        public string $currency = 'KHR',
        public bool $isActive = true,
        public ?array $metadata = null
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

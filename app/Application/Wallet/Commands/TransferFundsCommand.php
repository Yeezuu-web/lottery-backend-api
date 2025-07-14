<?php

namespace App\Application\Wallet\Commands;

use App\Domain\Wallet\ValueObjects\Money;

final class TransferFundsCommand
{
    public function __construct(
        public readonly int $fromWalletId,
        public readonly int $toWalletId,
        public readonly Money $amount,
        public readonly string $reference,
        public readonly string $description,
        public readonly ?array $metadata = null,
        public readonly ?int $orderId = null
    ) {}

    public function toArray(): array
    {
        return [
            'from_wallet_id' => $this->fromWalletId,
            'to_wallet_id' => $this->toWalletId,
            'amount' => $this->amount->toArray(),
            'reference' => $this->reference,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'order_id' => $this->orderId,
        ];
    }
}

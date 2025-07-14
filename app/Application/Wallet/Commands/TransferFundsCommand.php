<?php

declare(strict_types=1);

namespace App\Application\Wallet\Commands;

use App\Domain\Wallet\ValueObjects\Money;

final readonly class TransferFundsCommand
{
    public function __construct(
        public int $fromWalletId,
        public int $toWalletId,
        public Money $amount,
        public string $reference,
        public string $description,
        public ?array $metadata = null,
        public ?int $orderId = null
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

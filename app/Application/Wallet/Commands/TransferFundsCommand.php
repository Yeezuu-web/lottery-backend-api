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
        public ?int $orderId = null,
        public ?int $initiatorAgentId = null, // Agent who initiated the transfer
        public ?string $transferType = 'manual', // 'manual', 'commission', 'bonus', 'system'
        public ?array $businessRules = null // Additional business rules to validate
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
            'initiator_agent_id' => $this->initiatorAgentId,
            'transfer_type' => $this->transferType,
            'business_rules' => $this->businessRules,
        ];
    }

    public function isInterAgentTransfer(): bool
    {
        return $this->initiatorAgentId !== null;
    }

    public function isCommissionTransfer(): bool
    {
        return $this->transferType === 'commission';
    }

    public function isBonusTransfer(): bool
    {
        return $this->transferType === 'bonus';
    }

    public function isSystemTransfer(): bool
    {
        return $this->transferType === 'system';
    }

    public function isManualTransfer(): bool
    {
        return $this->transferType === 'manual';
    }
}

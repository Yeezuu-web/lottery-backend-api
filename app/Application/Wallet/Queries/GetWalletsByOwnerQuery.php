<?php

namespace App\Application\Wallet\Queries;

use App\Domain\Wallet\ValueObjects\WalletType;

final class GetWalletsByOwnerQuery
{
    public function __construct(
        public readonly int $ownerId,
        public readonly ?WalletType $walletType = null,
        public readonly bool $activeOnly = true,
        public readonly bool $includeBalance = true
    ) {}

    public function toArray(): array
    {
        return [
            'owner_id' => $this->ownerId,
            'wallet_type' => $this->walletType?->value,
            'active_only' => $this->activeOnly,
            'include_balance' => $this->includeBalance,
        ];
    }
}

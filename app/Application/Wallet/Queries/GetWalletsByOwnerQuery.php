<?php

declare(strict_types=1);

namespace App\Application\Wallet\Queries;

use App\Domain\Wallet\ValueObjects\WalletType;

final readonly class GetWalletsByOwnerQuery
{
    public function __construct(
        public int $ownerId,
        public ?WalletType $walletType = null,
        public bool $activeOnly = true,
        public bool $includeBalance = true
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

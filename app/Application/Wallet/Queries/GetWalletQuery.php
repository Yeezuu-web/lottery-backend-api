<?php

declare(strict_types=1);

namespace App\Application\Wallet\Queries;

final readonly class GetWalletQuery
{
    public function __construct(
        public int $walletId,
        public bool $includeBalance = true,
        public bool $includeTransactions = false,
        public ?int $transactionLimit = null
    ) {}

    public function toArray(): array
    {
        return [
            'wallet_id' => $this->walletId,
            'include_balance' => $this->includeBalance,
            'include_transactions' => $this->includeTransactions,
            'transaction_limit' => $this->transactionLimit,
        ];
    }
}

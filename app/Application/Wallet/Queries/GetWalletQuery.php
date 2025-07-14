<?php

namespace App\Application\Wallet\Queries;

final class GetWalletQuery
{
    public function __construct(
        public readonly int $walletId,
        public readonly bool $includeBalance = true,
        public readonly bool $includeTransactions = false,
        public readonly ?int $transactionLimit = null
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

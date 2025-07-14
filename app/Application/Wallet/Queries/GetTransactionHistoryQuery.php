<?php

namespace App\Application\Wallet\Queries;

use App\Domain\Wallet\ValueObjects\TransactionStatus;
use App\Domain\Wallet\ValueObjects\TransactionType;
use Carbon\Carbon;

final class GetTransactionHistoryQuery
{
    public function __construct(
        public readonly int $walletId,
        public readonly ?TransactionType $transactionType = null,
        public readonly ?TransactionStatus $status = null,
        public readonly ?Carbon $fromDate = null,
        public readonly ?Carbon $toDate = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly string $sortBy = 'created_at',
        public readonly string $sortDirection = 'desc'
    ) {}

    public function toArray(): array
    {
        return [
            'wallet_id' => $this->walletId,
            'transaction_type' => $this->transactionType?->value,
            'status' => $this->status?->value,
            'from_date' => $this->fromDate?->toISOString(),
            'to_date' => $this->toDate?->toISOString(),
            'page' => $this->page,
            'per_page' => $this->perPage,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
        ];
    }
}

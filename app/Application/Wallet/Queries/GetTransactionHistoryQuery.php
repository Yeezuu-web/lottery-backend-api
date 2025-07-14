<?php

declare(strict_types=1);

namespace App\Application\Wallet\Queries;

use App\Domain\Wallet\ValueObjects\TransactionStatus;
use App\Domain\Wallet\ValueObjects\TransactionType;
use Carbon\Carbon;

final readonly class GetTransactionHistoryQuery
{
    public function __construct(
        public int $walletId,
        public ?TransactionType $transactionType = null,
        public ?TransactionStatus $status = null,
        public ?Carbon $fromDate = null,
        public ?Carbon $toDate = null,
        public int $page = 1,
        public int $perPage = 20,
        public string $sortBy = 'created_at',
        public string $sortDirection = 'desc'
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

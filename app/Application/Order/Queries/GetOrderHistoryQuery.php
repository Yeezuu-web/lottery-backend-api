<?php

namespace App\Application\Order\Queries;

final readonly class GetOrderHistoryQuery
{
    public function __construct(
        private int $agentId,
        private array $filters = [],
        private int $limit = 10,
        private int $offset = 0
    ) {}

    public function agentId(): int
    {
        return $this->agentId;
    }

    public function filters(): array
    {
        return $this->filters;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function offset(): int
    {
        return $this->offset;
    }
}

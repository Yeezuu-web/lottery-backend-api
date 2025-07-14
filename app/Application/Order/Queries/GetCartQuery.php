<?php

declare(strict_types=1);

namespace App\Application\Order\Queries;

final readonly class GetCartQuery
{
    public function __construct(
        private int $agentId
    ) {}

    public function agentId(): int
    {
        return $this->agentId;
    }
}

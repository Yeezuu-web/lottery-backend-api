<?php

declare(strict_types=1);

namespace App\Application\Order\Commands;

final readonly class CancelOrderCommand
{
    public function __construct(
        private int $agentId,
        private string $orderNumber,
        private string $reason = ''
    ) {}

    public function agentId(): int
    {
        return $this->agentId;
    }

    public function orderNumber(): string
    {
        return $this->orderNumber;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}

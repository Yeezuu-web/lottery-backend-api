<?php

namespace App\Application\Order\Commands;

final readonly class RemoveFromCartCommand
{
    public function __construct(
        private int $agentId,
        private int $cartItemId
    ) {}

    public function agentId(): int
    {
        return $this->agentId;
    }

    public function cartItemId(): int
    {
        return $this->cartItemId;
    }
}

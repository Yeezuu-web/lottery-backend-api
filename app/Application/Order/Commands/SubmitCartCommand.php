<?php

declare(strict_types=1);

namespace App\Application\Order\Commands;

final readonly class SubmitCartCommand
{
    public function __construct(
        private int $agentId
    ) {}

    public function agentId(): int
    {
        return $this->agentId;
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Order\Commands;

final readonly class AddToCartCommand
{
    public function __construct(
        private int $agentId,
        private string $period,
        private string $type,
        private array $channels,
        private string $option,
        private string $number,
        private float $amount,
        private string $currency = 'KHR'
    ) {}

    public function agentId(): int
    {
        return $this->agentId;
    }

    public function period(): string
    {
        return $this->period;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function channels(): array
    {
        return $this->channels;
    }

    public function option(): string
    {
        return $this->option;
    }

    public function number(): string
    {
        return $this->number;
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }
}

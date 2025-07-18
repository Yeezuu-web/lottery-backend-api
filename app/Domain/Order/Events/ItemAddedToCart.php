<?php

declare(strict_types=1);

namespace App\Domain\Order\Events;

use App\Domain\Order\ValueObjects\BetData;
use DateTimeImmutable;

final readonly class ItemAddedToCart
{
    public function __construct(
        private int $agentId,
        private BetData $betData,
        private array $expandedNumbers,
        private array $channelWeights,
        private DateTimeImmutable $occurredAt
    ) {}

    public static function now(int $agentId, BetData $betData, array $expandedNumbers, array $channelWeights): self
    {
        return new self($agentId, $betData, $expandedNumbers, $channelWeights, new DateTimeImmutable);
    }

    public function agentId(): int
    {
        return $this->agentId;
    }

    public function betData(): BetData
    {
        return $this->betData;
    }

    public function expandedNumbers(): array
    {
        return $this->expandedNumbers;
    }

    public function channelWeights(): array
    {
        return $this->channelWeights;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'agent_id' => $this->agentId,
            'period' => $this->betData->period(),
            'type' => $this->betData->type(),
            'channels' => $this->betData->channels(),
            'number' => $this->betData->number(),
            'amount' => $this->betData->amount()->amount(),
            'currency' => $this->betData->amount()->currency(),
            'expanded_numbers' => $this->expandedNumbers,
            'channel_weights' => $this->channelWeights,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }
}

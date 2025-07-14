<?php

namespace App\Domain\Order\Events;

use App\Domain\Order\ValueObjects\GroupId;
use App\Domain\Wallet\ValueObjects\Money;
use DateTime;

final readonly class CartSubmitted
{
    public function __construct(
        private int $agentId,
        private GroupId $groupId,
        private array $orderIds,
        private Money $totalAmount,
        private DateTime $occurredAt
    ) {}

    public function agentId(): int
    {
        return $this->agentId;
    }

    public function groupId(): GroupId
    {
        return $this->groupId;
    }

    public function orderIds(): array
    {
        return $this->orderIds;
    }

    public function totalAmount(): Money
    {
        return $this->totalAmount;
    }

    public function occurredAt(): DateTime
    {
        return $this->occurredAt;
    }

    public static function now(int $agentId, GroupId $groupId, array $orderIds, Money $totalAmount): self
    {
        return new self($agentId, $groupId, $orderIds, $totalAmount, new DateTime);
    }

    public function toArray(): array
    {
        return [
            'agent_id' => $this->agentId,
            'group_id' => $this->groupId->value(),
            'order_ids' => $this->orderIds,
            'total_amount' => $this->totalAmount->amount(),
            'currency' => $this->totalAmount->currency(),
            'order_count' => count($this->orderIds),
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }
}

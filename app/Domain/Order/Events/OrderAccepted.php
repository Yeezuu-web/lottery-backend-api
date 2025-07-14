<?php

declare(strict_types=1);

namespace App\Domain\Order\Events;

use App\Domain\Order\Models\Order;
use DateTimeImmutable;

final readonly class OrderAccepted
{
    public function __construct(
        private Order $order,
        private DateTimeImmutable $occurredAt
    ) {}

    public static function now(Order $order): self
    {
        return new self($order, new DateTimeImmutable);
    }

    public function order(): Order
    {
        return $this->order;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->order->id(),
            'agent_id' => $this->order->agentId(),
            'order_number' => $this->order->orderNumber()->value(),
            'group_id' => $this->order->groupId()->value(),
            'total_amount' => $this->order->totalAmount()->amount(),
            'currency' => $this->order->totalAmount()->currency(),
            'status' => $this->order->status(),
            'accepted_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }
}

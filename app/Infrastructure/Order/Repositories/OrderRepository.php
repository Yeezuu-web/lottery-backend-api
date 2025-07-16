<?php

declare(strict_types=1);

namespace App\Infrastructure\Order\Repositories;

use App\Application\Order\Contracts\OrderRepositoryInterface;
use App\Domain\Agent\Models\Agent;
use App\Domain\Order\Models\Order;
use App\Domain\Order\ValueObjects\BetData;
use App\Domain\Order\ValueObjects\GroupId;
use App\Domain\Order\ValueObjects\OrderNumber;
use App\Domain\Wallet\ValueObjects\Money;
use App\Infrastructure\Order\Models\EloquentOrder;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final readonly class OrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private EloquentOrder $model
    ) {}

    public function save(Order $order): Order
    {
        $eloquentOrder = $this->model->find($order->id());

        if (! $eloquentOrder) {
            $eloquentOrder = new EloquentOrder;
        }

        $eloquentOrder->agent_id = $order->agentId();
        $eloquentOrder->order_number = $order->orderNumber()->value();
        $eloquentOrder->group_id = $order->groupId()->value();
        $eloquentOrder->bet_data = $order->betData()->toArray();
        $eloquentOrder->expanded_numbers = $order->expandedNumbers();
        $eloquentOrder->channel_weights = $order->channelWeights();
        $eloquentOrder->total_amount = $order->totalAmount()->amount();
        $eloquentOrder->currency = $order->totalAmount()->currency();
        $eloquentOrder->status = $order->status();
        $eloquentOrder->is_printed = $order->isPrinted();
        $eloquentOrder->printed_at = $order->printedAt();
        $eloquentOrder->placed_at = $order->placedAt();

        $eloquentOrder->save();

        return $this->toDomainModel($eloquentOrder);
    }

    public function findById(int $id): ?Order
    {
        $eloquentOrder = $this->model->find($id);

        if (! $eloquentOrder) {
            return null;
        }

        return $this->toDomainModel($eloquentOrder);
    }

    public function findByOrderNumber(OrderNumber $orderNumber): ?Order
    {
        $eloquentOrder = $this->model->where('order_number', $orderNumber->value())->first();

        if (! $eloquentOrder) {
            return null;
        }

        return $this->toDomainModel($eloquentOrder);
    }

    public function findByGroupId(GroupId $groupId): array
    {
        $eloquentOrders = $this->model
            ->where('group_id', $groupId->value())
            ->orderBy('placed_at', 'desc')
            ->get();

        return $eloquentOrders->map(fn ($order): Order => $this->toDomainModel($order))->toArray();
    }

    public function findByAgent(Agent $agent, array $filters = [], int $limit = 10, int $offset = 0): array
    {
        $query = $this->model
            ->where('agent_id', $agent->id())
            ->orderBy('placed_at', 'desc');

        // Apply filters
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('placed_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('placed_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['type'])) {
            $query->whereJsonContains('bet_data->type', $filters['type']);
        }

        if (! empty($filters['period'])) {
            $query->whereJsonContains('bet_data->period', $filters['period']);
        }

        $eloquentOrders = $query->limit($limit)->offset($offset)->get();

        return $eloquentOrders->map(fn ($order): Order => $this->toDomainModel($order))->toArray();
    }

    public function findByStatus(string $status, int $limit = 10, int $offset = 0): array
    {
        $eloquentOrders = $this->model
            ->where('status', $status)
            ->orderBy('placed_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $eloquentOrders->map(fn ($order): Order => $this->toDomainModel($order))->toArray();
    }

    public function findByDateRange(DateTimeImmutable $startDate, DateTimeImmutable $endDate, int $limit = 10, int $offset = 0): array
    {
        $eloquentOrders = $this->model
            ->whereDate('placed_at', '>=', $startDate->format('Y-m-d'))
            ->whereDate('placed_at', '<=', $endDate->format('Y-m-d'))
            ->orderBy('placed_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $eloquentOrders->map(fn ($order): Order => $this->toDomainModel($order))->toArray();
    }

    public function countByAgent(Agent $agent, array $filters = []): int
    {
        $query = $this->model->where('agent_id', $agent->id());

        // Apply filters
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('placed_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('placed_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['type'])) {
            $query->whereJsonContains('bet_data->type', $filters['type']);
        }

        if (! empty($filters['period'])) {
            $query->whereJsonContains('bet_data->period', $filters['period']);
        }

        return $query->count();
    }

    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    public function findPrintableOrders(Agent $agent): array
    {
        $eloquentOrders = $this->model
            ->where('agent_id', $agent->id())
            ->where('status', 'accepted')
            ->where('is_printed', false)
            ->orderBy('placed_at', 'desc')
            ->get();

        return $eloquentOrders->map(fn ($order): Order => $this->toDomainModel($order))->toArray();
    }

    public function bulkUpdateStatus(array $orderIds, string $status): bool
    {
        $updated = $this->model
            ->whereIn('id', $orderIds)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]);

        return $updated > 0;
    }

    public function findByAgentId(int $agentId, ?string $status = null, int $limit = 100): array
    {
        $query = $this->model
            ->where('agent_id', $agentId)
            ->orderBy('placed_at', 'desc');

        if ($status !== null && $status !== '' && $status !== '0') {
            $query->where('status', $status);
        }

        $eloquentOrders = $query->limit($limit)->get();

        return $eloquentOrders->map(fn ($order): Order => $this->toDomainModel($order))->toArray();
    }

    public function findUnprintedByGroupId(GroupId $groupId): array
    {
        $eloquentOrders = $this->model
            ->where('group_id', $groupId->value())
            ->where('is_printed', false)
            ->orderBy('placed_at', 'desc')
            ->get();

        return $eloquentOrders->map(fn ($order): Order => $this->toDomainModel($order))->toArray();
    }

    public function markGroupAsPrinted(GroupId $groupId): void
    {
        $this->model
            ->where('group_id', $groupId->value())
            ->where('is_printed', false)
            ->update([
                'is_printed' => true,
                'printed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function delete(Order $order): bool
    {
        if (! in_array($order->id(), [null, 0], true)) {
            return $this->model->destroy($order->id()) > 0;
        }

        return false;
    }

    private function toDomainModel(EloquentOrder $eloquentOrder): Order
    {
        return new Order(
            id: $eloquentOrder->id,
            agentId: $eloquentOrder->agent_id,
            orderNumber: OrderNumber::fromString($eloquentOrder->order_number),
            groupId: GroupId::fromString($eloquentOrder->group_id),
            betData: BetData::fromArray($eloquentOrder->bet_data),
            expandedNumbers: $eloquentOrder->expanded_numbers,
            channelWeights: $eloquentOrder->channel_weights,
            totalAmount: Money::fromAmount($eloquentOrder->total_amount, $eloquentOrder->currency),
            status: $eloquentOrder->status,
            isPrinted: $eloquentOrder->is_printed,
            printedAt: $eloquentOrder->printed_at ? DateTimeImmutable::createFromMutable($eloquentOrder->printed_at) : null,
            placedAt: DateTimeImmutable::createFromMutable($eloquentOrder->placed_at),
            createdAt: DateTimeImmutable::createFromMutable($eloquentOrder->created_at),
            updatedAt: DateTimeImmutable::createFromMutable($eloquentOrder->updated_at)
        );
    }
}

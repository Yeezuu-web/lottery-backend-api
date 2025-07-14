<?php

namespace App\Application\Order\Queries\Handlers;

use App\Application\Order\Contracts\OrderRepositoryInterface;
use App\Application\Order\Queries\GetOrderHistoryQuery;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Order\Exceptions\OrderException;

final readonly class GetOrderHistoryQueryHandler
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private AgentRepositoryInterface $agentRepository
    ) {}

    public function handle(GetOrderHistoryQuery $query): array
    {
        // 1. Validate and get agent
        $agent = $this->agentRepository->findById($query->agentId());
        if (! $agent) {
            throw OrderException::invalidAgent($query->agentId());
        }

        // 2. Get orders
        $orders = $this->orderRepository->findByAgent(
            $agent,
            $query->filters(),
            $query->limit(),
            $query->offset()
        );

        // 3. Get total count
        $totalCount = $this->orderRepository->countByAgent($agent, $query->filters());

        // 4. Return result
        return [
            'orders' => $orders,
            'total_count' => $totalCount,
            'limit' => $query->limit(),
            'offset' => $query->offset(),
            'agent_id' => $agent->id(),
            'agent_name' => $agent->username(),
        ];
    }
}

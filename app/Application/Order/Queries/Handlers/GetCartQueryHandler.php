<?php

declare(strict_types=1);

namespace App\Application\Order\Queries\Handlers;

use App\Application\Order\Contracts\CartRepositoryInterface;
use App\Application\Order\Queries\GetCartQuery;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Domain\Order\Exceptions\OrderException;

final readonly class GetCartQueryHandler
{
    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private AgentRepositoryInterface $agentRepository
    ) {}

    public function handle(GetCartQuery $query): array
    {
        // 1. Validate and get agent
        $agent = $this->agentRepository->findById($query->agentId());
        if (! $agent instanceof \App\Domain\Agent\Models\Agent) {
            throw OrderException::invalidAgent($query->agentId());
        }

        // 2. Get cart items
        $cartItems = $this->cartRepository->getItems($agent);

        // 3. Get cart summary
        $cartSummary = $this->cartRepository->getCartSummary($agent);

        // 4. Return result
        return [
            'items' => $cartItems,
            'summary' => $cartSummary,
            'agent_id' => $agent->id(),
            'agent_name' => $agent->username(),
        ];
    }
}

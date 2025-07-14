<?php

namespace App\Infrastructure\Order\Repositories;

use App\Application\Order\Contracts\CartRepositoryInterface;
use App\Domain\Agent\Models\Agent;
use App\Domain\Order\ValueObjects\BetData;
use App\Domain\Wallet\ValueObjects\Money;
use App\Infrastructure\Order\Models\EloquentCart;
use DateTime;

final class CartRepository implements CartRepositoryInterface
{
    public function __construct(
        private EloquentCart $model
    ) {}

    public function addItem(Agent $agent, BetData $betData, array $expandedNumbers, array $channelWeights): array
    {
        $totalAmount = $this->calculateTotalAmount($betData, $expandedNumbers, $channelWeights);

        $cartItem = new EloquentCart;
        $cartItem->agent_id = $agent->id();
        $cartItem->bet_data = $betData->toArray();
        $cartItem->expanded_numbers = $expandedNumbers;
        $cartItem->channel_weights = $channelWeights;
        $cartItem->total_amount = $totalAmount->amount();
        $cartItem->currency = $totalAmount->currency();
        $cartItem->status = 'active';

        $cartItem->save();

        return [
            'id' => $cartItem->id,
            'agent_id' => $cartItem->agent_id,
            'bet_data' => $betData,
            'expanded_numbers' => $expandedNumbers,
            'channel_weights' => $channelWeights,
            'total_amount' => $totalAmount->amount(),
            'currency' => $totalAmount->currency(),
            'status' => $cartItem->status,
            'created_at' => new DateTime($cartItem->created_at),
            'updated_at' => new DateTime($cartItem->updated_at),
        ];
    }

    public function getItems(Agent $agent): array
    {
        $cartItems = $this->model
            ->where('agent_id', $agent->id())
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        return $cartItems->map(function ($item) {
            return [
                'id' => $item->id,
                'agent_id' => $item->agent_id,
                'bet_data' => BetData::fromArray($item->bet_data),
                'expanded_numbers' => $item->expanded_numbers,
                'channel_weights' => $item->channel_weights,
                'total_amount' => $item->total_amount,
                'currency' => $item->currency,
                'status' => $item->status,
                'created_at' => new DateTime($item->created_at),
                'updated_at' => new DateTime($item->updated_at),
            ];
        })->toArray();
    }

    public function removeItem(Agent $agent, int $itemId): bool
    {
        $deleted = $this->model
            ->where('id', $itemId)
            ->where('agent_id', $agent->id())
            ->where('status', 'active')
            ->delete();

        return $deleted > 0;
    }

    public function clearCart(Agent $agent): void
    {
        $this->model
            ->where('agent_id', $agent->id())
            ->where('status', 'active')
            ->delete();
    }

    public function hasExistingItem(Agent $agent, BetData $betData): bool
    {
        return $this->model
            ->where('agent_id', $agent->id())
            ->where('status', 'active')
            ->where('bet_data', $betData->toArray())
            ->exists();
    }

    public function getCartSummary(Agent $agent): array
    {
        $items = $this->getItems($agent);
        $totalAmount = 0;
        $currency = 'KHR';

        foreach ($items as $item) {
            $totalAmount += $item['total_amount'];
            $currency = $item['currency'];
        }

        return [
            'total_items' => count($items),
            'total_amount' => $totalAmount,
            'currency' => $currency,
        ];
    }

    public function updateItem(Agent $agent, int $itemId, BetData $betData, array $expandedNumbers, array $channelWeights): array
    {
        $totalAmount = $this->calculateTotalAmount($betData, $expandedNumbers, $channelWeights);

        $cartItem = $this->model
            ->where('id', $itemId)
            ->where('agent_id', $agent->id())
            ->where('status', 'active')
            ->first();

        if (!$cartItem) {
            throw new \Exception('Cart item not found');
        }

        $cartItem->bet_data = $betData->toArray();
        $cartItem->expanded_numbers = $expandedNumbers;
        $cartItem->channel_weights = $channelWeights;
        $cartItem->total_amount = $totalAmount->amount();
        $cartItem->currency = $totalAmount->currency();
        $cartItem->updated_at = new DateTime;

        $cartItem->save();

        return [
            'id' => $cartItem->id,
            'agent_id' => $cartItem->agent_id,
            'bet_data' => $betData,
            'expanded_numbers' => $expandedNumbers,
            'channel_weights' => $channelWeights,
            'total_amount' => $totalAmount->amount(),
            'currency' => $totalAmount->currency(),
            'status' => $cartItem->status,
            'created_at' => new DateTime($cartItem->created_at),
            'updated_at' => $cartItem->updated_at,
        ];
    }

    public function getItemCount(Agent $agent): int
    {
        return $this->model
            ->where('agent_id', $agent->id())
            ->where('status', 'active')
            ->count();
    }

    public function getTotalAmount(Agent $agent): float
    {
        return $this->model
            ->where('agent_id', $agent->id())
            ->where('status', 'active')
            ->sum('total_amount');
    }

    public function markCartAsSubmitted(Agent $agent): void
    {
        $this->model
            ->where('agent_id', $agent->id())
            ->where('status', 'active')
            ->update([
                'status' => 'submitted',
                'updated_at' => new DateTime,
            ]);
    }

    private function calculateTotalAmount(BetData $betData, array $expandedNumbers, array $channelWeights): Money
    {
        $baseAmount = $betData->amount();
        $expansionCount = count($expandedNumbers);
        $totalWeight = array_sum($channelWeights);
        $multiplier = $expansionCount * $totalWeight;

        return $baseAmount->multiply($multiplier);
    }
}
